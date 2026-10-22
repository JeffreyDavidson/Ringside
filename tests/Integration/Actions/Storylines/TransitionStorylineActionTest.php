<?php

declare(strict_types=1);
use App\Actions\Storylines\TransitionStepAction;
use App\Actions\Storylines\TransitionStorylineAction;
use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineRevisionOperation;
use App\Enums\Storylines\StorylineStatus;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineStep;

use function Pest\Laravel\actingAs;

test('archive freezes a paused story and restores its prior state explicitly', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    // Act
    resolve(TransitionStorylineAction::class)->handle($story, 1, StorylineStatus::Paused, 'Wait for the next tour.', signedInUser());
    resolve(TransitionStorylineAction::class)->handle($story, $story->refresh()->version, StorylineStatus::Archived, 'Keep for later.', signedInUser());
    resolve(TransitionStorylineAction::class)->handle($story, $story->refresh()->version, StorylineStatus::Paused, 'Resume planning.', signedInUser());
    // Assert
    expect($story->refresh()->status)->toBe(StorylineStatus::Paused)
        ->and($story->revisions()->count())->toBe(3);
});

require_once __DIR__.'/../../../Fixtures/storylines.php';
test('completion requires every pending or postponed plan to be resolved', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    expect(fn () => resolve(TransitionStorylineAction::class)->handle($story, $story->refresh()->version, StorylineStatus::Completed, 'Done.', signedInUser()))->toThrow(StorylineConflictException::class);
    resolve(TransitionStepAction::class)->handle($story, $story->refresh()->version, $step->id, StepStatus::Cancelled, 'Unused.', signedInUser());
    resolve(TransitionStorylineAction::class)->handle($story, $story->refresh()->version, StorylineStatus::Completed, 'Finished.', signedInUser());
    expect(fn () => resolve(TransitionStorylineAction::class)->handle($story, $story->refresh()->version, StorylineStatus::Paused, 'Invalid.', signedInUser()))->toThrow(StorylineConflictException::class);
    resolve(TransitionStorylineAction::class)->handle($story, $story->refresh()->version, StorylineStatus::Active, 'New chapter.', signedInUser());
    expect($story->refresh()->status)->toBe(StorylineStatus::Active);
});

test('a story makes every allowed status move and keeps the status it was archived from', function (StorylineStatus $from, ?StorylineStatus $previous, StorylineStatus $to, ?StorylineStatus $previousAfter) {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()
        ->create(['status' => $from, 'previous_status' => $previous]);

    // Act
    resolve(TransitionStorylineAction::class)->handle($story, 1, $to, 'Reviewed.', signedInUser());

    // Assert
    $story->refresh();
    expect($story->status)->toBe($to)
        ->and($story->previous_status)->toBe($previousAfter)
        ->and($story->version)->toBe(2)
        ->and($story->revisions()->sole()->operation)->toBe(StorylineRevisionOperation::StoryStatus);
})->with([
    'active to paused' => [StorylineStatus::Active, null, StorylineStatus::Paused, null],
    'active to completed' => [StorylineStatus::Active, null, StorylineStatus::Completed, null],
    'active to archived' => [StorylineStatus::Active, null, StorylineStatus::Archived, StorylineStatus::Active],
    'paused to active' => [StorylineStatus::Paused, null, StorylineStatus::Active, null],
    'paused to completed' => [StorylineStatus::Paused, null, StorylineStatus::Completed, null],
    'paused to archived' => [StorylineStatus::Paused, null, StorylineStatus::Archived, StorylineStatus::Paused],
    'completed to active' => [StorylineStatus::Completed, null, StorylineStatus::Active, null],
    'completed to archived' => [StorylineStatus::Completed, null, StorylineStatus::Archived, StorylineStatus::Completed],
    'archived back to active' => [StorylineStatus::Archived, StorylineStatus::Active, StorylineStatus::Active, StorylineStatus::Active],
    'archived back to paused' => [StorylineStatus::Archived, StorylineStatus::Paused, StorylineStatus::Paused, StorylineStatus::Paused],
    'archived back to completed' => [StorylineStatus::Archived, StorylineStatus::Completed, StorylineStatus::Completed, StorylineStatus::Completed],
]);

test('a story refuses every other status move and stays as it was', function (StorylineStatus $from, ?StorylineStatus $previous, StorylineStatus $to) {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()
        ->create(['status' => $from, 'previous_status' => $previous]);

    // Act
    $transition = fn () => resolve(TransitionStorylineAction::class)->handle($story, 1, $to, 'Reviewed.', signedInUser());

    // Assert
    expect($transition)->toThrow(StorylineConflictException::class, __('storylines.state_error'));

    $story->refresh();
    expect($story->status)->toBe($from)
        ->and($story->previous_status)->toBe($previous)
        ->and($story->version)->toBe(1)
        ->and($story->revisions()->count())->toBe(0);
})->with([
    'active to active' => [StorylineStatus::Active, null, StorylineStatus::Active],
    'paused to paused' => [StorylineStatus::Paused, null, StorylineStatus::Paused],
    'completed to completed' => [StorylineStatus::Completed, null, StorylineStatus::Completed],
    'completed to paused' => [StorylineStatus::Completed, null, StorylineStatus::Paused],
    'archived to archived' => [StorylineStatus::Archived, StorylineStatus::Active, StorylineStatus::Archived],
    'archived to paused when it was active' => [StorylineStatus::Archived, StorylineStatus::Active, StorylineStatus::Paused],
    'archived to completed when it was active' => [StorylineStatus::Archived, StorylineStatus::Active, StorylineStatus::Completed],
    'archived to active when it was paused' => [StorylineStatus::Archived, StorylineStatus::Paused, StorylineStatus::Active],
    'archived to active with no earlier status' => [StorylineStatus::Archived, null, StorylineStatus::Active],
]);

test('a story with an open step cannot be completed', function (StorylineStatus $from, StepStatus $open) {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()
        ->create(['status' => $from]);
    StorylineStep::factory()
        ->for($story)
        ->create(['status' => $open]);

    // Act
    $transition = fn () => resolve(TransitionStorylineAction::class)->handle($story, 1, StorylineStatus::Completed, 'Done.', signedInUser());

    // Assert
    expect($transition)->toThrow(StorylineConflictException::class, __('storylines.state_error'))
        ->and($story->refresh()->status)->toBe($from);
})->with([
    'active with a planned step' => [StorylineStatus::Active, StepStatus::Planned],
    'paused with a postponed step' => [StorylineStatus::Paused, StepStatus::Postponed],
]);

test('a story with only resolved steps can be completed', function (StepStatus $resolved) {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()
        ->create();
    StorylineStep::factory()
        ->for($story)
        ->create(['status' => $resolved]);

    // Act
    resolve(TransitionStorylineAction::class)->handle($story, 1, StorylineStatus::Completed, 'Done.', signedInUser());

    // Assert
    expect($story->refresh()->status)->toBe(StorylineStatus::Completed);
})->with([StepStatus::Cancelled, StepStatus::Happened]);

test('a story status change records the actor passed in', function () {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $story = Storyline::factory()->create();

    // Act
    resolve(TransitionStorylineAction::class)->handle($story, 1, StorylineStatus::Paused, 'Wait for the next tour.', $actor);

    // Assert
    expect($story->revisions()->sole()->actor_id)->toBe($actor->id);
});
