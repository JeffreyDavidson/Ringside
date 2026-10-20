<?php

declare(strict_types=1);

use App\Enums\Events\SponsorCommitmentStatus;
use App\Exceptions\Sponsors\SponsorCommitmentException;
use App\Lifecycle\Events\SponsorCommitmentStateMachine;
use App\Models\Events\EventSponsorCommitment;
use Illuminate\Support\Carbon;

/** Every from → to pair of the three statuses, with whether the move is allowed and whether it needs a reason. */
dataset('sponsor commitment moves', [
    'pending to pending' => [SponsorCommitmentStatus::Pending, SponsorCommitmentStatus::Pending, true, false],
    'pending to completed' => [SponsorCommitmentStatus::Pending, SponsorCommitmentStatus::Completed, true, false],
    'pending to cancelled' => [SponsorCommitmentStatus::Pending, SponsorCommitmentStatus::Cancelled, true, true],
    'completed to pending' => [SponsorCommitmentStatus::Completed, SponsorCommitmentStatus::Pending, true, true],
    'completed to completed' => [SponsorCommitmentStatus::Completed, SponsorCommitmentStatus::Completed, true, true],
    'completed to cancelled' => [SponsorCommitmentStatus::Completed, SponsorCommitmentStatus::Cancelled, true, true],
    'cancelled to pending' => [SponsorCommitmentStatus::Cancelled, SponsorCommitmentStatus::Pending, true, true],
    'cancelled to completed' => [SponsorCommitmentStatus::Cancelled, SponsorCommitmentStatus::Completed, true, true],
    'cancelled to cancelled' => [SponsorCommitmentStatus::Cancelled, SponsorCommitmentStatus::Cancelled, true, true],
]);

describe('sponsor commitment state machine', function (): void {
    it('allows every move between the three statuses', function (SponsorCommitmentStatus $from, SponsorCommitmentStatus $to, bool $allowed): void {
        // Arrange
        $machine = new SponsorCommitmentStateMachine;

        // Act
        $canMove = $machine->canMove($from, $to);

        // Assert
        expect($canMove)->toBe($allowed);
    })->with('sponsor commitment moves');

    it('asks for a reason when cancelling or changing a promise that is no longer pending', function (SponsorCommitmentStatus $from, SponsorCommitmentStatus $to, bool $allowed, bool $reason): void {
        // Arrange
        $machine = new SponsorCommitmentStateMachine;

        // Act
        $requiresReason = $machine->requiresReason($from, $to);

        // Assert
        expect($requiresReason)->toBe($reason);
    })->with('sponsor commitment moves');

    it('lets a promise make every move that needs a reason when it has one', function (SponsorCommitmentStatus $from, SponsorCommitmentStatus $to): void {
        // Arrange
        $machine = new SponsorCommitmentStateMachine;
        $commitment = EventSponsorCommitment::factory()->make(['status' => $from]);

        // Act
        $move = fn () => $machine->ensureCanMove($commitment, $to, 'Sponsor withdrew');

        // Assert
        expect($move)->not->toThrow(SponsorCommitmentException::class);
    })->with([
        'pending to cancelled' => [SponsorCommitmentStatus::Pending, SponsorCommitmentStatus::Cancelled],
        'completed to pending' => [SponsorCommitmentStatus::Completed, SponsorCommitmentStatus::Pending],
        'completed to completed' => [SponsorCommitmentStatus::Completed, SponsorCommitmentStatus::Completed],
        'completed to cancelled' => [SponsorCommitmentStatus::Completed, SponsorCommitmentStatus::Cancelled],
        'cancelled to pending' => [SponsorCommitmentStatus::Cancelled, SponsorCommitmentStatus::Pending],
        'cancelled to completed' => [SponsorCommitmentStatus::Cancelled, SponsorCommitmentStatus::Completed],
        'cancelled to cancelled' => [SponsorCommitmentStatus::Cancelled, SponsorCommitmentStatus::Cancelled],
    ]);

    it('lets a pending promise stay pending or complete without a reason', function (SponsorCommitmentStatus $to): void {
        // Arrange
        $machine = new SponsorCommitmentStateMachine;
        $commitment = EventSponsorCommitment::factory()->make(['status' => SponsorCommitmentStatus::Pending]);

        // Act
        $move = fn () => $machine->ensureCanMove($commitment, $to, null);

        // Assert
        expect($move)->not->toThrow(SponsorCommitmentException::class);
    })->with([
        'pending' => [SponsorCommitmentStatus::Pending],
        'completed' => [SponsorCommitmentStatus::Completed],
    ]);

    it('refuses a move that needs a reason when none is given', function (SponsorCommitmentStatus $from, SponsorCommitmentStatus $to): void {
        // Arrange
        $machine = new SponsorCommitmentStateMachine;
        $commitment = EventSponsorCommitment::factory()->make(['status' => $from]);

        // Act
        $move = fn () => $machine->ensureCanMove($commitment, $to, null);

        // Assert
        expect($move)->toThrow(SponsorCommitmentException::class, __('sponsors.reason_required'));
    })->with([
        'pending to cancelled' => [SponsorCommitmentStatus::Pending, SponsorCommitmentStatus::Cancelled],
        'completed to pending' => [SponsorCommitmentStatus::Completed, SponsorCommitmentStatus::Pending],
        'completed to completed' => [SponsorCommitmentStatus::Completed, SponsorCommitmentStatus::Completed],
        'completed to cancelled' => [SponsorCommitmentStatus::Completed, SponsorCommitmentStatus::Cancelled],
        'cancelled to pending' => [SponsorCommitmentStatus::Cancelled, SponsorCommitmentStatus::Pending],
        'cancelled to completed' => [SponsorCommitmentStatus::Cancelled, SponsorCommitmentStatus::Completed],
        'cancelled to cancelled' => [SponsorCommitmentStatus::Cancelled, SponsorCommitmentStatus::Cancelled],
    ]);

    it('treats a blank reason as no reason', function (?string $reason): void {
        // Arrange
        $machine = new SponsorCommitmentStateMachine;
        $commitment = EventSponsorCommitment::factory()->make(['status' => SponsorCommitmentStatus::Completed]);

        // Act
        $move = fn () => $machine->ensureCanMove($commitment, SponsorCommitmentStatus::Pending, $reason);

        // Assert
        expect($move)->toThrow(SponsorCommitmentException::class, __('sponsors.reason_required'));
    })->with([null, '', '   ']);

    it('starts a new promise from pending', function (SponsorCommitmentStatus $to): void {
        // Arrange
        $machine = new SponsorCommitmentStateMachine;

        // Act
        $move = fn () => $machine->ensureCanMove(null, $to, null);

        // Assert
        expect($move)->not->toThrow(SponsorCommitmentException::class);
    })->with([
        'pending' => [SponsorCommitmentStatus::Pending],
        'completed' => [SponsorCommitmentStatus::Completed],
    ]);

    it('asks a new promise for a reason when it starts cancelled', function (): void {
        // Arrange
        $machine = new SponsorCommitmentStateMachine;

        // Act
        $move = fn () => $machine->ensureCanMove(null, SponsorCommitmentStatus::Cancelled, null);

        // Assert
        expect($move)->toThrow(SponsorCommitmentException::class, __('sponsors.reason_required'));
    });

    it('lets a completion with evidence and no future completion time through', function (?Carbon $completedAt): void {
        // Arrange
        $machine = new SponsorCommitmentStateMachine;

        // Act
        $complete = fn () => $machine->ensureCanComplete(SponsorCommitmentStatus::Completed, 'Photo checked at doors', $completedAt);

        // Assert
        expect($complete)->not->toThrow(SponsorCommitmentException::class);
    })->with([
        'no completion time' => [null],
        'a past completion time' => [Carbon::parse('2026-01-01 12:00:00')],
    ]);

    it('refuses a completion without evidence or with a completion time in the future', function (?string $evidence, ?Carbon $completedAt): void {
        // Arrange
        $machine = new SponsorCommitmentStateMachine;

        // Act
        $complete = fn () => $machine->ensureCanComplete(SponsorCommitmentStatus::Completed, $evidence, $completedAt);

        // Assert
        expect($complete)->toThrow(SponsorCommitmentException::class, __('sponsors.evidence_required'));
    })->with([
        'no evidence' => [null, null],
        'empty evidence' => ['', null],
        'blank evidence' => ['   ', null],
        'a future completion time' => ['Photo checked at doors', Carbon::parse('2999-01-01 12:00:00')],
    ]);

    it('asks for no evidence unless the promise is completed', function (SponsorCommitmentStatus $to): void {
        // Arrange
        $machine = new SponsorCommitmentStateMachine;

        // Act
        $complete = fn () => $machine->ensureCanComplete($to, null, Carbon::parse('2999-01-01 12:00:00'));

        // Assert
        expect($complete)->not->toThrow(SponsorCommitmentException::class);
    })->with([
        'pending' => [SponsorCommitmentStatus::Pending],
        'cancelled' => [SponsorCommitmentStatus::Cancelled],
    ]);
});
