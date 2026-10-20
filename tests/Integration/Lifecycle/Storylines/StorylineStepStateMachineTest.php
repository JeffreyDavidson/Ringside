<?php

declare(strict_types=1);

use App\Enums\Storylines\StepStatus;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Lifecycle\Storylines\StorylineStepStateMachine;
use App\Models\Storylines\StorylineStep;

/** Every from → to pair of the four step statuses, with whether the move is allowed. */
dataset('storyline step moves', [
    'planned to planned' => [StepStatus::Planned, StepStatus::Planned, false],
    'planned to postponed' => [StepStatus::Planned, StepStatus::Postponed, true],
    'planned to cancelled' => [StepStatus::Planned, StepStatus::Cancelled, true],
    'planned to happened' => [StepStatus::Planned, StepStatus::Happened, false],
    'postponed to planned' => [StepStatus::Postponed, StepStatus::Planned, true],
    'postponed to postponed' => [StepStatus::Postponed, StepStatus::Postponed, false],
    'postponed to cancelled' => [StepStatus::Postponed, StepStatus::Cancelled, true],
    'postponed to happened' => [StepStatus::Postponed, StepStatus::Happened, false],
    'cancelled to planned' => [StepStatus::Cancelled, StepStatus::Planned, true],
    'cancelled to postponed' => [StepStatus::Cancelled, StepStatus::Postponed, true],
    'cancelled to cancelled' => [StepStatus::Cancelled, StepStatus::Cancelled, false],
    'cancelled to happened' => [StepStatus::Cancelled, StepStatus::Happened, false],
    'happened to planned' => [StepStatus::Happened, StepStatus::Planned, false],
    'happened to postponed' => [StepStatus::Happened, StepStatus::Postponed, false],
    'happened to cancelled' => [StepStatus::Happened, StepStatus::Cancelled, false],
    'happened to happened' => [StepStatus::Happened, StepStatus::Happened, false],
]);

describe('storyline step state machine', function (): void {
    it('allows only the moves in the table', function (StepStatus $from, StepStatus $to, bool $allowed): void {
        // Arrange
        $machine = new StorylineStepStateMachine;

        // Act
        $canMove = $machine->canMove($from, $to);

        // Assert
        expect($canMove)->toBe($allowed);
    })->with('storyline step moves');

    it('lets a step make a move the table allows', function (StepStatus $from, StepStatus $to): void {
        // Arrange
        $machine = new StorylineStepStateMachine;
        $step = StorylineStep::factory()
            ->make(['status' => $from]);

        // Act
        $move = fn () => $machine->ensureCanMove($step, $to);

        // Assert
        expect($move)->not->toThrow(StorylineConflictException::class);
    })->with([
        'planned to postponed' => [StepStatus::Planned, StepStatus::Postponed],
        'planned to cancelled' => [StepStatus::Planned, StepStatus::Cancelled],
        'postponed to planned' => [StepStatus::Postponed, StepStatus::Planned],
        'postponed to cancelled' => [StepStatus::Postponed, StepStatus::Cancelled],
        'cancelled to planned' => [StepStatus::Cancelled, StepStatus::Planned],
        'cancelled to postponed' => [StepStatus::Cancelled, StepStatus::Postponed],
    ]);

    it('refuses a move the table does not allow', function (StepStatus $from, StepStatus $to): void {
        // Arrange
        $machine = new StorylineStepStateMachine;
        $step = StorylineStep::factory()
            ->make(['status' => $from]);

        // Act
        $move = fn () => $machine->ensureCanMove($step, $to);

        // Assert
        expect($move)->toThrow(StorylineConflictException::class, __('storylines.state_error'));
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
});
