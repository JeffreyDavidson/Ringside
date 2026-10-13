<?php

declare(strict_types=1);
use App\Actions\Storylines\CreateAction;
use App\Data\Storylines\StorylineData;
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
    $story = resolve(CreateAction::class)->handle($promotion, $data);
    $replayed = resolve(CreateAction::class)->handle($promotion, $data);

    // Assert
    expect($replayed->id)->toBe($story->id)
        ->and(Storyline::query()->count())->toBe(1)
        ->and(StorylineRevision::query()->firstOrFail()->after['payoff'])->toBe($data->payoff)
        ->and($story->promotion_id)->toBe($promotion->id);
});
