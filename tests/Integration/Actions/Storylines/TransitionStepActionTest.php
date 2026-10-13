<?php

declare(strict_types=1);
use App\Actions\Storylines\SaveStepAction;
use App\Actions\Storylines\TransitionStepAction;
use App\Data\Storylines\SourceData;
use App\Data\Storylines\StepData;
use App\Enums\Storylines\StepStatus;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Models\Storylines\Storyline;
use Illuminate\Support\Str;

use function Pest\Laravel\actingAs;

test('postponement and resumption preserve the plan and their reasons', function () {
    // Arrange
    actingAs(administrator());
    $story = Storyline::factory()->create();
    $step = resolve(SaveStepAction::class)->handle($story, 1, new StepData('Betrayal', 'Attack the partner.', 1, new SourceData, (string) Str::uuid()));

    // Act
    resolve(TransitionStepAction::class)->handle($story, $story->refresh()->version, $step->id, StepStatus::Postponed, 'Talent is unavailable.');
    resolve(TransitionStepAction::class)->handle($story, $story->refresh()->version, $step->id, StepStatus::Planned, 'Ready for the next show.');

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
    expect(fn () => resolve(TransitionStepAction::class)->handle($story, $story->refresh()->version, $step->id, $status, 'Reviewed.'))->toThrow(StorylineConflictException::class);
})->with([StepStatus::Planned, StepStatus::Happened]);
