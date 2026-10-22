<?php

declare(strict_types=1);
use App\Actions\Storylines\RecordDevelopmentAction;
use App\Actions\Storylines\RetractDevelopmentAction;
use App\Data\Storylines\DevelopmentData;
use App\Data\Storylines\SourceData;
use App\Enums\Storylines\StepStatus;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Storylines\Storyline;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('a retraction retains the recorded account and its reason', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $development = resolve(RecordDevelopmentAction::class)->handle($story, 1, new DevelopmentData('The warning', 'A confrontation happened.', today()->toDateString(), 1, [], new SourceData, (string) Str::uuid()));
    // Act
    resolve(RetractDevelopmentAction::class)->handle($story, $story->refresh()->version, $development->id, 'It was a rehearsal.', false);
    // Assert
    expect($development->refresh()->retracted_at)->not->toBeNull()
        ->and($development->description)->toBe('A confrontation happened.')
        ->and($story->revisions()->orderByDesc('id')->firstOrFail()->reason)->toBe('It was a rehearsal.');
});

require_once __DIR__.'/../../../Fixtures/storylines.php';
test('retracting explicitly reopens or cancels the linked step without losing its account', function (bool $reopen) {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    $development = confirmedStorylineDevelopment($story, $step->id);
    resolve(RetractDevelopmentAction::class)->handle($story, $story->refresh()->version, $development->id, 'It was a rehearsal.', $reopen);
    expect($step->refresh()->status)->toBe($reopen ? StepStatus::Planned : StepStatus::Cancelled)
        ->and(fn () => resolve(RetractDevelopmentAction::class)->handle($story, $story->refresh()->version, $development->id, 'Repeated.', $reopen))->toThrow(StorylineConflictException::class);
})->with([true, false]);

test('a retraction records the actor passed in', function () {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $story = Storyline::factory()->create();
    $development = confirmedStorylineDevelopment($story);

    // Act
    resolve(RetractDevelopmentAction::class)->handle($story, $story->refresh()->version, $development->id, 'It was a rehearsal.', false, $actor);

    // Assert
    expect($story->revisions()->orderByDesc('id')->firstOrFail()->actor_id)->toBe($actor->id);
});
