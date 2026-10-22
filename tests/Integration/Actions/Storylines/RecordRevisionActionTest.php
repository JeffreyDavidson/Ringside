<?php

declare(strict_types=1);
use App\Actions\Storylines\RecordRevisionAction;
use App\Enums\Storylines\StorylineRevisionOperation;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineRevision;
use Illuminate\Support\Facades\Artisan;

use function Pest\Laravel\actingAs;

test('durable revisions survive general activity log retention and retain actor and account', function () {
    actingAs($actor = administrator());
    $story = Storyline::factory()->create(['payoff' => 'Private payoff.']);
    resolve(RecordRevisionAction::class)->handle($story, $story, StorylineRevisionOperation::StoryRevised, ['payoff' => 'Original payoff.'], 'A new direction.');
    StorylineRevision::query()->update(['created_at' => now()->subYears(2)]);
    Artisan::call('activitylog:clean');
    $revision = $story->revisions()->with(['actor', 'storyline'])->firstOrFail();
    expect($revision->before['payoff'])->toBe('Original payoff.')
        ->and($revision->after['payoff'])->toBe('Private payoff.')
        ->and($revision->actor?->id)->toBe($actor->id)
        ->and($revision->storyline->id)->toBe($story->id);
});

test('a revision records the actor passed in, not the signed-in user', function () {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $story = Storyline::factory()->create();

    // Act
    resolve(RecordRevisionAction::class)->handle($story, $story, StorylineRevisionOperation::StoryRevised, $actor, ['payoff' => 'Original payoff.'], 'A new direction.');

    // Assert
    expect(StorylineRevision::query()->sole()->actor_id)->toBe($actor->id);
});

test('a revision can be recorded with nobody signed in', function () {
    // Arrange
    $actor = administrator();
    $story = Storyline::factory()->create();

    // Act
    resolve(RecordRevisionAction::class)->handle($story, $story, StorylineRevisionOperation::StoryRevised, $actor);

    // Assert
    expect(auth()->check())->toBeFalse()
        ->and(StorylineRevision::query()->sole()->actor_id)->toBe($actor->id);
});
