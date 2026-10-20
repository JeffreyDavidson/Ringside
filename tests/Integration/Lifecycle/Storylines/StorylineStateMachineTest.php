<?php

declare(strict_types=1);

use App\Enums\Storylines\StorylineStatus;
use App\Exceptions\Storylines\StorylineConflictException;
use App\Lifecycle\Storylines\StorylineStateMachine;
use App\Models\Storylines\Storyline;

/** Every from → to pair of the four statuses, with whether the table allows the move. */
dataset('storyline moves', [
    'active to active' => [StorylineStatus::Active, StorylineStatus::Active, false],
    'active to paused' => [StorylineStatus::Active, StorylineStatus::Paused, true],
    'active to completed' => [StorylineStatus::Active, StorylineStatus::Completed, true],
    'active to archived' => [StorylineStatus::Active, StorylineStatus::Archived, true],
    'paused to active' => [StorylineStatus::Paused, StorylineStatus::Active, true],
    'paused to paused' => [StorylineStatus::Paused, StorylineStatus::Paused, false],
    'paused to completed' => [StorylineStatus::Paused, StorylineStatus::Completed, true],
    'paused to archived' => [StorylineStatus::Paused, StorylineStatus::Archived, true],
    'completed to active' => [StorylineStatus::Completed, StorylineStatus::Active, true],
    'completed to paused' => [StorylineStatus::Completed, StorylineStatus::Paused, false],
    'completed to completed' => [StorylineStatus::Completed, StorylineStatus::Completed, false],
    'completed to archived' => [StorylineStatus::Completed, StorylineStatus::Archived, true],
    'archived to active' => [StorylineStatus::Archived, StorylineStatus::Active, true],
    'archived to paused' => [StorylineStatus::Archived, StorylineStatus::Paused, true],
    'archived to completed' => [StorylineStatus::Archived, StorylineStatus::Completed, true],
    'archived to archived' => [StorylineStatus::Archived, StorylineStatus::Archived, false],
]);

describe('storyline state machine', function (): void {
    it('allows only the moves in the table', function (StorylineStatus $from, StorylineStatus $to, bool $allowed): void {
        // Arrange
        $machine = new StorylineStateMachine;

        // Act
        $canMove = $machine->canMove($from, $to);

        // Assert
        expect($canMove)->toBe($allowed);
    })->with('storyline moves');

    it('lets a story make a move the table allows', function (StorylineStatus $from, ?StorylineStatus $previous, StorylineStatus $to): void {
        // Arrange
        $machine = new StorylineStateMachine;
        $story = Storyline::factory()
            ->make(['status' => $from, 'previous_status' => $previous]);

        // Act
        $move = fn () => $machine->ensureCanMove($story, $to, false);

        // Assert
        expect($move)->not->toThrow(StorylineConflictException::class);
    })->with([
        'active to paused' => [StorylineStatus::Active, null, StorylineStatus::Paused],
        'active to completed' => [StorylineStatus::Active, null, StorylineStatus::Completed],
        'active to archived' => [StorylineStatus::Active, null, StorylineStatus::Archived],
        'paused to active' => [StorylineStatus::Paused, null, StorylineStatus::Active],
        'paused to completed' => [StorylineStatus::Paused, null, StorylineStatus::Completed],
        'paused to archived' => [StorylineStatus::Paused, null, StorylineStatus::Archived],
        'completed to active' => [StorylineStatus::Completed, null, StorylineStatus::Active],
        'completed to archived' => [StorylineStatus::Completed, null, StorylineStatus::Archived],
        'archived back to active' => [StorylineStatus::Archived, StorylineStatus::Active, StorylineStatus::Active],
        'archived back to paused' => [StorylineStatus::Archived, StorylineStatus::Paused, StorylineStatus::Paused],
        'archived back to completed' => [StorylineStatus::Archived, StorylineStatus::Completed, StorylineStatus::Completed],
    ]);

    it('refuses a move the table does not allow', function (StorylineStatus $from, StorylineStatus $to): void {
        // Arrange
        $machine = new StorylineStateMachine;
        $story = Storyline::factory()
            ->make(['status' => $from, 'previous_status' => $to]);

        // Act
        $move = fn () => $machine->ensureCanMove($story, $to, false);

        // Assert
        expect($move)->toThrow(StorylineConflictException::class, __('storylines.state_error'));
    })->with([
        'active to active' => [StorylineStatus::Active, StorylineStatus::Active],
        'paused to paused' => [StorylineStatus::Paused, StorylineStatus::Paused],
        'completed to paused' => [StorylineStatus::Completed, StorylineStatus::Paused],
        'completed to completed' => [StorylineStatus::Completed, StorylineStatus::Completed],
        'archived to archived' => [StorylineStatus::Archived, StorylineStatus::Archived],
    ]);

    it('refuses to bring an archived story back to any status but the one it was archived from', function (StorylineStatus $previous, StorylineStatus $to): void {
        // Arrange
        $machine = new StorylineStateMachine;
        $story = Storyline::factory()
            ->make(['status' => StorylineStatus::Archived, 'previous_status' => $previous]);

        // Act
        $move = fn () => $machine->ensureCanMove($story, $to, false);

        // Assert
        expect($move)->toThrow(StorylineConflictException::class, __('storylines.state_error'));
    })->with([
        'paused when it was active' => [StorylineStatus::Active, StorylineStatus::Paused],
        'completed when it was active' => [StorylineStatus::Active, StorylineStatus::Completed],
        'active when it was paused' => [StorylineStatus::Paused, StorylineStatus::Active],
        'active when it was completed' => [StorylineStatus::Completed, StorylineStatus::Active],
    ]);

    it('refuses to bring an archived story back when it remembers no earlier status', function (StorylineStatus $to): void {
        // Arrange
        $machine = new StorylineStateMachine;
        $story = Storyline::factory()
            ->make(['status' => StorylineStatus::Archived, 'previous_status' => null]);

        // Act
        $move = fn () => $machine->ensureCanMove($story, $to, false);

        // Assert
        expect($move)->toThrow(StorylineConflictException::class, __('storylines.state_error'));
    })->with([StorylineStatus::Active, StorylineStatus::Paused, StorylineStatus::Completed]);

    it('refuses to complete a story that still has planned or postponed steps', function (StorylineStatus $from, ?StorylineStatus $previous): void {
        // Arrange
        $machine = new StorylineStateMachine;
        $story = Storyline::factory()
            ->make(['status' => $from, 'previous_status' => $previous]);

        // Act
        $move = fn () => $machine->ensureCanMove($story, StorylineStatus::Completed, true);

        // Assert
        expect($move)->toThrow(StorylineConflictException::class, __('storylines.state_error'));
    })->with([
        'an active story' => [StorylineStatus::Active, null],
        'a paused story' => [StorylineStatus::Paused, null],
        'an archived story that was completed' => [StorylineStatus::Archived, StorylineStatus::Completed],
    ]);

    it('ignores open steps for a move that does not complete the story', function (StorylineStatus $to): void {
        // Arrange
        $machine = new StorylineStateMachine;
        $story = Storyline::factory()
            ->make(['status' => StorylineStatus::Active]);

        // Act
        $move = fn () => $machine->ensureCanMove($story, $to, true);

        // Assert
        expect($move)->not->toThrow(StorylineConflictException::class);
    })->with([StorylineStatus::Paused, StorylineStatus::Archived]);

    it('lets an active or paused story be edited', function (StorylineStatus $status): void {
        // Arrange
        $machine = new StorylineStateMachine;
        $story = Storyline::factory()
            ->make(['status' => $status]);

        // Act
        $edit = fn () => $machine->ensureOpen($story);

        // Assert
        expect($edit)->not->toThrow(StorylineConflictException::class);
    })->with([StorylineStatus::Active, StorylineStatus::Paused]);

    it('refuses to edit a completed or archived story', function (StorylineStatus $status): void {
        // Arrange
        $machine = new StorylineStateMachine;
        $story = Storyline::factory()
            ->make(['status' => $status]);

        // Act
        $edit = fn () => $machine->ensureOpen($story);

        // Assert
        expect($edit)->toThrow(StorylineConflictException::class, __('storylines.state_error'));
    })->with([StorylineStatus::Completed, StorylineStatus::Archived]);
});
