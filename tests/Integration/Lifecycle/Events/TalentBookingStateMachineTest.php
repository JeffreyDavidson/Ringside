<?php

declare(strict_types=1);

use App\Enums\Events\TalentBookingStatus;
use App\Exceptions\Events\TalentBookingException;
use App\Lifecycle\Events\TalentBookingStateMachine;
use App\Models\Events\EventTalentBooking;

/** Every from → to pair of the four statuses, with whether the move is allowed and whether it needs a reason. */
dataset('talent booking moves', [
    'proposed to proposed' => [TalentBookingStatus::Proposed, TalentBookingStatus::Proposed, true, false],
    'proposed to contacted' => [TalentBookingStatus::Proposed, TalentBookingStatus::Contacted, true, false],
    'proposed to confirmed' => [TalentBookingStatus::Proposed, TalentBookingStatus::Confirmed, true, false],
    'proposed to cancelled' => [TalentBookingStatus::Proposed, TalentBookingStatus::Cancelled, true, true],
    'contacted to proposed' => [TalentBookingStatus::Contacted, TalentBookingStatus::Proposed, true, false],
    'contacted to contacted' => [TalentBookingStatus::Contacted, TalentBookingStatus::Contacted, true, false],
    'contacted to confirmed' => [TalentBookingStatus::Contacted, TalentBookingStatus::Confirmed, true, false],
    'contacted to cancelled' => [TalentBookingStatus::Contacted, TalentBookingStatus::Cancelled, true, true],
    'confirmed to proposed' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Proposed, true, true],
    'confirmed to contacted' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Contacted, true, true],
    'confirmed to confirmed' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Confirmed, true, false],
    'confirmed to cancelled' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Cancelled, true, true],
    'cancelled to proposed' => [TalentBookingStatus::Cancelled, TalentBookingStatus::Proposed, true, false],
    'cancelled to contacted' => [TalentBookingStatus::Cancelled, TalentBookingStatus::Contacted, false, false],
    'cancelled to confirmed' => [TalentBookingStatus::Cancelled, TalentBookingStatus::Confirmed, false, false],
    'cancelled to cancelled' => [TalentBookingStatus::Cancelled, TalentBookingStatus::Cancelled, false, true],
]);

describe('talent booking state machine', function (): void {
    it('allows only the moves in the table', function (TalentBookingStatus $from, TalentBookingStatus $to, bool $allowed): void {
        // Arrange
        $machine = new TalentBookingStateMachine;

        // Act
        $canMove = $machine->canMove($from, $to);

        // Assert
        expect($canMove)->toBe($allowed);
    })->with('talent booking moves');

    it('asks for a reason when cancelling or correcting a confirmation', function (TalentBookingStatus $from, TalentBookingStatus $to, bool $allowed, bool $reason): void {
        // Arrange
        $machine = new TalentBookingStateMachine;

        // Act
        $requiresReason = $machine->requiresReason($from, $to);

        // Assert
        expect($requiresReason)->toBe($reason);
    })->with('talent booking moves');

    it('lets a booking make every allowed move when it has a reason', function (TalentBookingStatus $from, TalentBookingStatus $to): void {
        // Arrange
        $machine = new TalentBookingStateMachine;
        $booking = EventTalentBooking::factory()->make(['status' => $from]);

        // Act
        $move = fn () => $machine->ensureCanMove($booking, $to, 'Agreed with the talent');

        // Assert
        expect($move)->not->toThrow(TalentBookingException::class);
    })->with([
        'proposed to proposed' => [TalentBookingStatus::Proposed, TalentBookingStatus::Proposed],
        'proposed to contacted' => [TalentBookingStatus::Proposed, TalentBookingStatus::Contacted],
        'proposed to confirmed' => [TalentBookingStatus::Proposed, TalentBookingStatus::Confirmed],
        'proposed to cancelled' => [TalentBookingStatus::Proposed, TalentBookingStatus::Cancelled],
        'contacted to proposed' => [TalentBookingStatus::Contacted, TalentBookingStatus::Proposed],
        'contacted to contacted' => [TalentBookingStatus::Contacted, TalentBookingStatus::Contacted],
        'contacted to confirmed' => [TalentBookingStatus::Contacted, TalentBookingStatus::Confirmed],
        'contacted to cancelled' => [TalentBookingStatus::Contacted, TalentBookingStatus::Cancelled],
        'confirmed to proposed' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Proposed],
        'confirmed to contacted' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Contacted],
        'confirmed to confirmed' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Confirmed],
        'confirmed to cancelled' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Cancelled],
        'cancelled to proposed' => [TalentBookingStatus::Cancelled, TalentBookingStatus::Proposed],
    ]);

    it('lets a booking make a move that needs no reason without one', function (TalentBookingStatus $from, TalentBookingStatus $to): void {
        // Arrange
        $machine = new TalentBookingStateMachine;
        $booking = EventTalentBooking::factory()->make(['status' => $from]);

        // Act
        $move = fn () => $machine->ensureCanMove($booking, $to, null);

        // Assert
        expect($move)->not->toThrow(TalentBookingException::class);
    })->with([
        'proposed to proposed' => [TalentBookingStatus::Proposed, TalentBookingStatus::Proposed],
        'proposed to contacted' => [TalentBookingStatus::Proposed, TalentBookingStatus::Contacted],
        'proposed to confirmed' => [TalentBookingStatus::Proposed, TalentBookingStatus::Confirmed],
        'contacted to proposed' => [TalentBookingStatus::Contacted, TalentBookingStatus::Proposed],
        'contacted to contacted' => [TalentBookingStatus::Contacted, TalentBookingStatus::Contacted],
        'contacted to confirmed' => [TalentBookingStatus::Contacted, TalentBookingStatus::Confirmed],
        'confirmed to confirmed' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Confirmed],
        'cancelled to proposed' => [TalentBookingStatus::Cancelled, TalentBookingStatus::Proposed],
    ]);

    it('refuses a move that needs a reason when none is given', function (TalentBookingStatus $from, TalentBookingStatus $to): void {
        // Arrange
        $machine = new TalentBookingStateMachine;
        $booking = EventTalentBooking::factory()->make(['status' => $from]);

        // Act
        $move = fn () => $machine->ensureCanMove($booking, $to, null);

        // Assert
        expect($move)->toThrow(TalentBookingException::class, __('talent-bookings.errors.reason'));
    })->with([
        'proposed to cancelled' => [TalentBookingStatus::Proposed, TalentBookingStatus::Cancelled],
        'contacted to cancelled' => [TalentBookingStatus::Contacted, TalentBookingStatus::Cancelled],
        'confirmed to cancelled' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Cancelled],
        'confirmed to proposed' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Proposed],
        'confirmed to contacted' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Contacted],
    ]);

    it('refuses to reopen a cancelled booking as anything but proposed', function (TalentBookingStatus $from, TalentBookingStatus $to): void {
        // Arrange
        $machine = new TalentBookingStateMachine;
        $booking = EventTalentBooking::factory()->make(['status' => $from]);

        // Act
        $move = fn () => $machine->ensureCanMove($booking, $to, 'Agreed with the talent');

        // Assert
        expect($move)->toThrow(TalentBookingException::class, __('talent-bookings.errors.reopen'));
    })->with([
        'cancelled to contacted' => [TalentBookingStatus::Cancelled, TalentBookingStatus::Contacted],
        'cancelled to confirmed' => [TalentBookingStatus::Cancelled, TalentBookingStatus::Confirmed],
        'cancelled to cancelled' => [TalentBookingStatus::Cancelled, TalentBookingStatus::Cancelled],
    ]);

    it('treats a blank reason as no reason', function (?string $reason): void {
        // Arrange
        $machine = new TalentBookingStateMachine;
        $booking = EventTalentBooking::factory()->make(['status' => TalentBookingStatus::Contacted]);

        // Act
        $move = fn () => $machine->ensureCanMove($booking, TalentBookingStatus::Cancelled, $reason);

        // Assert
        expect($move)->toThrow(TalentBookingException::class, __('talent-bookings.errors.reason'));
    })->with([null, '', '   ']);

    it('starts a new booking from proposed', function (TalentBookingStatus $to): void {
        // Arrange
        $machine = new TalentBookingStateMachine;

        // Act
        $move = fn () => $machine->ensureCanMove(null, $to, null);

        // Assert
        expect($move)->not->toThrow(TalentBookingException::class);
    })->with([
        'proposed' => [TalentBookingStatus::Proposed],
        'contacted' => [TalentBookingStatus::Contacted],
        'confirmed' => [TalentBookingStatus::Confirmed],
    ]);

    it('asks a new booking for a reason when it starts cancelled', function (): void {
        // Arrange
        $machine = new TalentBookingStateMachine;

        // Act
        $move = fn () => $machine->ensureCanMove(null, TalentBookingStatus::Cancelled, null);

        // Assert
        expect($move)->toThrow(TalentBookingException::class, __('talent-bookings.errors.reason'));
    });

    it('refuses reopening before it asks for a reason', function (): void {
        // Arrange
        $machine = new TalentBookingStateMachine;
        $booking = EventTalentBooking::factory()->make(['status' => TalentBookingStatus::Cancelled]);

        // Act
        $move = fn () => $machine->ensureCanMove($booking, TalentBookingStatus::Cancelled, null);

        // Assert
        expect($move)->toThrow(TalentBookingException::class, __('talent-bookings.errors.reopen'));
    });

    it('confirms a scheduled event once the agreement is recorded or already confirmed', function (?TalentBookingStatus $from, bool $attested): void {
        // Arrange
        $machine = new TalentBookingStateMachine;
        $booking = $from === null ? null : EventTalentBooking::factory()->make(['status' => $from]);

        // Act
        $confirm = fn () => $machine->ensureCanConfirm($booking, TalentBookingStatus::Confirmed, true, $attested);

        // Assert
        expect($confirm)->not->toThrow(TalentBookingException::class);
    })->with([
        'a new booking with an agreement' => [null, true],
        'a contacted booking with an agreement' => [TalentBookingStatus::Contacted, true],
        'a confirmed booking saved again without an agreement' => [TalentBookingStatus::Confirmed, false],
    ]);

    it('refuses to confirm without a recorded agreement or a scheduled event', function (?TalentBookingStatus $from, bool $scheduled, bool $attested): void {
        // Arrange
        $machine = new TalentBookingStateMachine;
        $booking = $from === null ? null : EventTalentBooking::factory()->make(['status' => $from]);

        // Act
        $confirm = fn () => $machine->ensureCanConfirm($booking, TalentBookingStatus::Confirmed, $scheduled, $attested);

        // Assert
        expect($confirm)->toThrow(TalentBookingException::class, __('talent-bookings.errors.confirm'));
    })->with([
        'a new booking without an agreement' => [null, true, false],
        'a contacted booking without an agreement' => [TalentBookingStatus::Contacted, true, false],
        'an unscheduled event' => [TalentBookingStatus::Contacted, false, true],
        'a confirmed booking on an unscheduled event' => [TalentBookingStatus::Confirmed, false, false],
    ]);

    it('has nothing to confirm for a move that is not a confirmation', function (): void {
        // Arrange
        $machine = new TalentBookingStateMachine;
        $booking = EventTalentBooking::factory()->make(['status' => TalentBookingStatus::Contacted]);

        // Act
        $confirm = fn () => $machine->ensureCanConfirm($booking, TalentBookingStatus::Proposed, false, false);

        // Assert
        expect($confirm)->not->toThrow(TalentBookingException::class);
    });
});
