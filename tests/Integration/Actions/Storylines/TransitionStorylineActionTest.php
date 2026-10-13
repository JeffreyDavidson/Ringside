<?php

declare(strict_types=1);
use App\Actions\Storylines\TransitionStepAction;
use App\Actions\Storylines\TransitionStorylineAction;
use App\Enums\Storylines\StepStatus;
use App\Enums\Storylines\StorylineStatus;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Storylines\Storyline;

use function Pest\Laravel\actingAs;

test('archive freezes a paused story and restores its prior state explicitly', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    // Act
    resolve(TransitionStorylineAction::class)->handle($story, 1, StorylineStatus::Paused, 'Wait for the next tour.');
    resolve(TransitionStorylineAction::class)->handle($story, $story->refresh()->version, StorylineStatus::Archived, 'Keep for later.');
    resolve(TransitionStorylineAction::class)->handle($story, $story->refresh()->version, StorylineStatus::Paused, 'Resume planning.');
    // Assert
    expect($story->refresh()->status)->toBe(StorylineStatus::Paused)
        ->and($story->revisions()->count())->toBe(3);
});

require_once __DIR__.'/../../../Fixtures/storylines.php';
test('completion requires every pending or postponed plan to be resolved', function () {
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = plannedStorylineStep($story);
    expect(fn () => resolve(TransitionStorylineAction::class)->handle($story, $story->refresh()->version, StorylineStatus::Completed, 'Done.'))->toThrow(StorylineConflictException::class);
    resolve(TransitionStepAction::class)->handle($story, $story->refresh()->version, $step->id, StepStatus::Cancelled, 'Unused.');
    resolve(TransitionStorylineAction::class)->handle($story, $story->refresh()->version, StorylineStatus::Completed, 'Finished.');
    expect(fn () => resolve(TransitionStorylineAction::class)->handle($story, $story->refresh()->version, StorylineStatus::Paused, 'Invalid.'))->toThrow(StorylineConflictException::class);
    resolve(TransitionStorylineAction::class)->handle($story, $story->refresh()->version, StorylineStatus::Active, 'New chapter.');
    expect($story->refresh()->status)->toBe(StorylineStatus::Active);
});
