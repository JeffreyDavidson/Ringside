<?php

declare(strict_types=1);

use App\Enums\Tournaments\TournamentStatus;
use App\Exceptions\Tournaments\TournamentException;
use App\Lifecycle\Tournaments\TournamentStateMachine;
use App\Models\Tournaments\Tournament;

/** Every from → to pair of the four statuses, with whether an Action may ask for that move. */
dataset('tournament moves', [
    'draft to draft' => [TournamentStatus::Draft, TournamentStatus::Draft, false],
    'draft to active' => [TournamentStatus::Draft, TournamentStatus::Active, true],
    'draft to completed' => [TournamentStatus::Draft, TournamentStatus::Completed, false],
    'draft to cancelled' => [TournamentStatus::Draft, TournamentStatus::Cancelled, true],
    'active to draft' => [TournamentStatus::Active, TournamentStatus::Draft, false],
    'active to active' => [TournamentStatus::Active, TournamentStatus::Active, true],
    'active to completed' => [TournamentStatus::Active, TournamentStatus::Completed, false],
    'active to cancelled' => [TournamentStatus::Active, TournamentStatus::Cancelled, true],
    'completed to draft' => [TournamentStatus::Completed, TournamentStatus::Draft, false],
    'completed to active' => [TournamentStatus::Completed, TournamentStatus::Active, false],
    'completed to completed' => [TournamentStatus::Completed, TournamentStatus::Completed, false],
    'completed to cancelled' => [TournamentStatus::Completed, TournamentStatus::Cancelled, false],
    'cancelled to draft' => [TournamentStatus::Cancelled, TournamentStatus::Draft, false],
    'cancelled to active' => [TournamentStatus::Cancelled, TournamentStatus::Active, false],
    'cancelled to completed' => [TournamentStatus::Cancelled, TournamentStatus::Completed, false],
    'cancelled to cancelled' => [TournamentStatus::Cancelled, TournamentStatus::Cancelled, false],
]);

dataset('allowed tournament moves', [
    'draft to active' => [TournamentStatus::Draft, TournamentStatus::Active],
    'draft to cancelled' => [TournamentStatus::Draft, TournamentStatus::Cancelled],
    'active to active' => [TournamentStatus::Active, TournamentStatus::Active],
    'active to cancelled' => [TournamentStatus::Active, TournamentStatus::Cancelled],
]);

dataset('refused tournament moves', [
    'draft to draft' => [TournamentStatus::Draft, TournamentStatus::Draft],
    'draft to completed' => [TournamentStatus::Draft, TournamentStatus::Completed],
    'active to draft' => [TournamentStatus::Active, TournamentStatus::Draft],
    'active to completed' => [TournamentStatus::Active, TournamentStatus::Completed],
    'completed to draft' => [TournamentStatus::Completed, TournamentStatus::Draft],
    'completed to active' => [TournamentStatus::Completed, TournamentStatus::Active],
    'completed to completed' => [TournamentStatus::Completed, TournamentStatus::Completed],
    'completed to cancelled' => [TournamentStatus::Completed, TournamentStatus::Cancelled],
    'cancelled to draft' => [TournamentStatus::Cancelled, TournamentStatus::Draft],
    'cancelled to active' => [TournamentStatus::Cancelled, TournamentStatus::Active],
    'cancelled to completed' => [TournamentStatus::Cancelled, TournamentStatus::Completed],
    'cancelled to cancelled' => [TournamentStatus::Cancelled, TournamentStatus::Cancelled],
]);

describe('tournament state machine', function (): void {
    it('lists which moves an Action may ask for', function (TournamentStatus $from, TournamentStatus $to, bool $allowed): void {
        // Arrange
        $machine = new TournamentStateMachine;

        // Act
        $canMove = $machine->canMove($from, $to);

        // Assert
        expect($canMove)->toBe($allowed);
    })->with('tournament moves');

    it('lets an allowed move through', function (TournamentStatus $from, TournamentStatus $to): void {
        // Arrange
        $machine = new TournamentStateMachine;
        $tournament = Tournament::factory()->make(['status' => $from]);

        // Act
        $move = fn () => $machine->ensureCanMove($tournament, $to);

        // Assert
        expect($move)->not->toThrow(TournamentException::class);
    })->with('allowed tournament moves');

    it('refuses any other move as a closed tournament', function (TournamentStatus $from, TournamentStatus $to): void {
        // Arrange
        $machine = new TournamentStateMachine;
        $tournament = Tournament::factory()->make(['status' => $from]);

        // Act
        $move = fn () => $machine->ensureCanMove($tournament, $to);

        // Assert
        expect($move)->toThrow(TournamentException::class, __('tournaments.errors.closed'));
    })->with('refused tournament moves');

    it('lets a draft or active tournament stay open for bookings', function (TournamentStatus $status): void {
        // Arrange
        $machine = new TournamentStateMachine;
        $tournament = Tournament::factory()->make(['status' => $status]);

        // Act
        $check = fn () => $machine->ensureOpen($tournament);

        // Assert
        expect($check)->not->toThrow(TournamentException::class);
    })->with([
        'draft' => [TournamentStatus::Draft],
        'active' => [TournamentStatus::Active],
    ]);

    it('closes a completed or cancelled tournament to bookings', function (TournamentStatus $status): void {
        // Arrange
        $machine = new TournamentStateMachine;
        $tournament = Tournament::factory()->make(['status' => $status]);

        // Act
        $check = fn () => $machine->ensureOpen($tournament);

        // Assert
        expect($check)->toThrow(TournamentException::class, __('tournaments.errors.closed'));
    })->with([
        'completed' => [TournamentStatus::Completed],
        'cancelled' => [TournamentStatus::Cancelled],
    ]);

    it('lets only an active tournament take a withdrawal, replacement or walkover', function (): void {
        // Arrange
        $machine = new TournamentStateMachine;
        $tournament = Tournament::factory()->make(['status' => TournamentStatus::Active]);

        // Act
        $check = fn () => $machine->ensureActive($tournament);

        // Assert
        expect($check)->not->toThrow(TournamentException::class);
    });

    it('refuses a withdrawal, replacement or walkover unless the tournament is active', function (TournamentStatus $status): void {
        // Arrange
        $machine = new TournamentStateMachine;
        $tournament = Tournament::factory()->make(['status' => $status]);

        // Act
        $check = fn () => $machine->ensureActive($tournament);

        // Assert
        expect($check)->toThrow(TournamentException::class, __('tournaments.errors.closed'));
    })->with([
        'draft' => [TournamentStatus::Draft],
        'completed' => [TournamentStatus::Completed],
        'cancelled' => [TournamentStatus::Cancelled],
    ]);

    it('lets only a draft tournament change its structure', function (): void {
        // Arrange
        $machine = new TournamentStateMachine;
        $tournament = Tournament::factory()->make(['status' => TournamentStatus::Draft]);

        // Act
        $check = fn () => $machine->ensureStructureEditable($tournament);

        // Assert
        expect($check)->not->toThrow(TournamentException::class);
    });

    it('freezes the structure once the tournament has left draft', function (TournamentStatus $status): void {
        // Arrange
        $machine = new TournamentStateMachine;
        $tournament = Tournament::factory()->make(['status' => $status]);

        // Act
        $check = fn () => $machine->ensureStructureEditable($tournament);

        // Assert
        expect($check)->toThrow(TournamentException::class, __('tournaments.errors.structure_frozen'));
    })->with([
        'active' => [TournamentStatus::Active],
        'completed' => [TournamentStatus::Completed],
        'cancelled' => [TournamentStatus::Cancelled],
    ]);

    it('lets every tournament but a cancelled one follow its results', function (TournamentStatus $status, bool $canProgress): void {
        // Arrange
        $machine = new TournamentStateMachine;
        $tournament = Tournament::factory()->make(['status' => $status]);

        // Act
        $follows = $machine->canProgress($tournament);

        // Assert
        expect($follows)->toBe($canProgress);
    })->with([
        'draft' => [TournamentStatus::Draft, true],
        'active' => [TournamentStatus::Active, true],
        'completed' => [TournamentStatus::Completed, true],
        'cancelled' => [TournamentStatus::Cancelled, false],
    ]);

    it('refuses a result change on a cancelled tournament', function (): void {
        // Arrange
        $machine = new TournamentStateMachine;
        $tournament = Tournament::factory()->make(['status' => TournamentStatus::Cancelled]);

        // Act
        $check = fn () => $machine->ensureCanProgress($tournament);

        // Assert
        expect($check)->toThrow(TournamentException::class, __('tournaments.errors.closed'));
    });

    it('lets a result change through on a tournament that is not cancelled', function (TournamentStatus $status): void {
        // Arrange
        $machine = new TournamentStateMachine;
        $tournament = Tournament::factory()->make(['status' => $status]);

        // Act
        $check = fn () => $machine->ensureCanProgress($tournament);

        // Assert
        expect($check)->not->toThrow(TournamentException::class);
    })->with([
        'draft' => [TournamentStatus::Draft],
        'active' => [TournamentStatus::Active],
        'completed' => [TournamentStatus::Completed],
    ]);

    it('activates only a draft tournament when a bout is booked', function (TournamentStatus $status, bool $activates): void {
        // Arrange
        $machine = new TournamentStateMachine;
        $tournament = Tournament::factory()->make(['status' => $status]);

        // Act
        $result = $machine->activates($tournament);

        // Assert
        expect($result)->toBe($activates);
    })->with([
        'draft' => [TournamentStatus::Draft, true],
        'active' => [TournamentStatus::Active, false],
        'completed' => [TournamentStatus::Completed, false],
        'cancelled' => [TournamentStatus::Cancelled, false],
    ]);
});
