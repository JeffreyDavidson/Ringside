<?php

declare(strict_types=1);
use App\Actions\Storylines\CreateAction;
use App\Data\Storylines\StorylineData;
use App\Exceptions\RecordChangedException;
use App\Models\Promotions\Promotion;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineRevision;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a private story is created once with its original revision', function () {
    // Arrange
    actingAs(administrator());
    $promotion = Promotion::factory()->create();
    $data = new StorylineData('The betrayal', 'Partners turn into rivals.', 'A title match three shows later.', (string) Str::uuid());

    // Act
    $story = resolve(CreateAction::class)->handle($promotion, $data, signedInUser());
    $replayed = resolve(CreateAction::class)->handle($promotion, $data, signedInUser());

    // Assert
    expect($replayed->id)->toBe($story->id)
        ->and(Storyline::query()->count())->toBe(1)
        ->and(StorylineRevision::query()->firstOrFail()->after['payoff'])->toBe($data->payoff)
        ->and($story->promotion_id)->toBe($promotion->id);
});

test('a submission identity already used by another promotion is refused', function () {
    // Arrange
    actingAs(administrator());
    $data = new StorylineData('The betrayal', 'Partners turn into rivals.', null, (string) Str::uuid());
    resolve(CreateAction::class)->handle(Promotion::factory()->create(), $data, signedInUser());
    $other = Promotion::factory()->create();

    // Act
    $attempt = fn () => resolve(CreateAction::class)->handle($other, $data, signedInUser());

    // Assert
    expect($attempt)->toThrow(RecordChangedException::class)
        ->and(Storyline::query()->count())->toBe(1);
});

test('a story records the actor passed in on its original revision', function () {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $data = new StorylineData('The betrayal', 'Partners turn into rivals.', null, (string) Str::uuid());

    // Act
    $story = resolve(CreateAction::class)->handle(Promotion::factory()->create(), $data, $actor);

    // Assert
    expect($story->revisions()->sole()->actor_id)->toBe($actor->id);
});
