<?php

declare(strict_types=1);
use App\Actions\Storylines\SaveStepAction;
use App\Actions\Storylines\TransitionStepAction;
use App\Data\Storylines\SourceData;
use App\Data\Storylines\StepData;
use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineRevisionOperation;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Storylines\Storyline;
use App\Models\Storylines\StorylineStep;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('postponement and resumption preserve the plan and their reasons', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = resolve(SaveStepAction::class)->handle($story, 1, new StepData('Betrayal', 'Attack the partner.', 1, new SourceData, (string) Str::uuid()), signedInUser());

    // Act
    resolve(TransitionStepAction::class)->handle($story, $story->refresh()->version, $step->id, StepStatus::Postponed, 'Talent is unavailable.', signedInUser());
    resolve(TransitionStepAction::class)->handle($story, $story->refresh()->version, $step->id, StepStatus::Planned, 'Ready for the next show.', signedInUser());

    // Assert
    expect($step->refresh()->status)->toBe(StepStatus::Planned)
        ->and($step->description)->toBe('Attack the partner.')
        ->and($story->revisions()->orderBy('id')->pluck('reason')->all())->toBe([null, 'Talent is unavailable.', 'Ready for the next show.']);
});

require_once __DIR__.'/../../../Fixtures/storylines.php';
test('confirmed outcomes cannot be forged through a status transition', function (StepStatus $status) {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    expect(fn () => resolve(TransitionStepAction::class)->handle($story, $story->refresh()->version, $step->id, $status, 'Reviewed.', signedInUser()))->toThrow(StorylineConflictException::class);
})->with([StepStatus::Planned, StepStatus::Happened]);

test('a step makes every allowed status move', function (StepStatus $from, StepStatus $to) {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()
        ->create();
    $step = StorylineStep::factory()
        ->for($story)
        ->create(['status' => $from]);

    // Act
    resolve(TransitionStepAction::class)->handle($story, 1, $step->id, $to, 'Reviewed.', signedInUser());

    // Assert
    $step->refresh();
    expect($step->status)->toBe($to)
        ->and($step->version)->toBe(2)
        ->and($story->refresh()->version)->toBe(2)
        ->and($story->revisions()->sole()->operation)->toBe(StorylineRevisionOperation::StepStatus);
})->with([
    'planned to postponed' => [StepStatus::Planned, StepStatus::Postponed],
    'planned to cancelled' => [StepStatus::Planned, StepStatus::Cancelled],
    'postponed to planned' => [StepStatus::Postponed, StepStatus::Planned],
    'postponed to cancelled' => [StepStatus::Postponed, StepStatus::Cancelled],
    'cancelled to planned' => [StepStatus::Cancelled, StepStatus::Planned],
    'cancelled to postponed' => [StepStatus::Cancelled, StepStatus::Postponed],
]);

test('a step refuses every other status move and stays as it was', function (StepStatus $from, StepStatus $to) {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()
        ->create();
    $step = StorylineStep::factory()
        ->for($story)
        ->create(['status' => $from]);

    // Act
    $transition = fn () => resolve(TransitionStepAction::class)->handle($story, 1, $step->id, $to, 'Reviewed.', signedInUser());

    // Assert
    expect($transition)->toThrow(StorylineConflictException::class, __('storylines.state_error'))
        ->and($step->refresh()->status)->toBe($from)
        ->and($step->version)->toBe(1)
        ->and($story->revisions()->count())->toBe(0);
})->with([
    'planned to planned' => [StepStatus::Planned, StepStatus::Planned],
    'planned to happened' => [StepStatus::Planned, StepStatus::Happened],
    'postponed to postponed' => [StepStatus::Postponed, StepStatus::Postponed],
    'postponed to happened' => [StepStatus::Postponed, StepStatus::Happened],
    'cancelled to cancelled' => [StepStatus::Cancelled, StepStatus::Cancelled],
    'cancelled to happened' => [StepStatus::Cancelled, StepStatus::Happened],
    'happened to planned' => [StepStatus::Happened, StepStatus::Planned],
    'happened to postponed' => [StepStatus::Happened, StepStatus::Postponed],
    'happened to cancelled' => [StepStatus::Happened, StepStatus::Cancelled],
    'happened to happened' => [StepStatus::Happened, StepStatus::Happened],
]);

test('a step status change records the actor passed in', function () {
    // Arrange
    actingAs(administrator());
    $actor = administrator();
    $story = Storyline::factory()->create();
    $step = StorylineStep::factory()->for($story)->create(['status' => StepStatus::Planned]);

    // Act
    resolve(TransitionStepAction::class)->handle($story, 1, $step->id, StepStatus::Postponed, 'Reviewed.', $actor);

    // Assert
    expect($story->revisions()->sole()->actor_id)->toBe($actor->id);
});
