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

    it('lets a booking make every allowed move when it has a reason', function (TalentBookingStatus $from, TalentBookingStatus $to, bool $allowed): void {
        // Arrange
        $machine = new TalentBookingStateMachine;
        $booking = EventTalentBooking::factory()->make(['status' => $from]);

        // Act
        $move = fn () => $machine->ensureCanMove($booking, $to, 'Agreed with the talent');

        // Assert
        if ($allowed) {
            expect($move)->not->toThrow(TalentBookingException::class);

            return;
        }

        expect($move)->toThrow(TalentBookingException::class, __('talent-bookings.errors.reopen'));
    })->with('talent booking moves');

    it('refuses a move that needs a reason when none is given', function (TalentBookingStatus $from, TalentBookingStatus $to, bool $allowed, bool $reason): void {
        // Arrange
        $machine = new TalentBookingStateMachine;
        $booking = EventTalentBooking::factory()->make(['status' => $from]);

        // Act
        $move = fn () => $machine->ensureCanMove($booking, $to, null);

        // Assert
        if (! $allowed) {
            expect($move)->toThrow(TalentBookingException::class, __('talent-bookings.errors.reopen'));

            return;
        }

        if ($reason) {
            expect($move)->toThrow(TalentBookingException::class, __('talent-bookings.errors.reason'));

            return;
        }

        expect($move)->not->toThrow(TalentBookingException::class);
    })->with('talent booking moves');

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
        if ($to === TalentBookingStatus::Cancelled) {
            expect($move)->toThrow(TalentBookingException::class, __('talent-bookings.errors.reason'));

            return;
        }

        expect($move)->not->toThrow(TalentBookingException::class);
    })->with(TalentBookingStatus::cases());

    it('refuses reopening before it asks for a reason', function (): void {
        // Arrange
        $machine = new TalentBookingStateMachine;
        $booking = EventTalentBooking::factory()->make(['status' => TalentBookingStatus::Cancelled]);

        // Act
        $move = fn () => $machine->ensureCanMove($booking, TalentBookingStatus::Cancelled, null);

        // Assert
        expect($move)->toThrow(TalentBookingException::class, __('talent-bookings.errors.reopen'));
    });

    it('confirms only a scheduled event with a recorded agreement', function (?TalentBookingStatus $from, TalentBookingStatus $to, bool $scheduled, bool $attested, bool $allowed): void {
        // Arrange
        $machine = new TalentBookingStateMachine;
        $booking = $from === null ? null : EventTalentBooking::factory()->make(['status' => $from]);

        // Act
        $confirm = fn () => $machine->ensureCanConfirm($booking, $to, $scheduled, $attested);

        // Assert
        if ($allowed) {
            expect($confirm)->not->toThrow(TalentBookingException::class);

            return;
        }

        expect($confirm)->toThrow(TalentBookingException::class, __('talent-bookings.errors.confirm'));
    })->with([
        'a new booking with an agreement' => [null, TalentBookingStatus::Confirmed, true, true, true],
        'a new booking without an agreement' => [null, TalentBookingStatus::Confirmed, true, false, false],
        'a contacted booking with an agreement' => [TalentBookingStatus::Contacted, TalentBookingStatus::Confirmed, true, true, true],
        'a contacted booking without an agreement' => [TalentBookingStatus::Contacted, TalentBookingStatus::Confirmed, true, false, false],
        'an unscheduled event' => [TalentBookingStatus::Contacted, TalentBookingStatus::Confirmed, false, true, false],
        'a confirmed booking saved again without an agreement' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Confirmed, true, false, true],
        'a confirmed booking on an unscheduled event' => [TalentBookingStatus::Confirmed, TalentBookingStatus::Confirmed, false, false, false],
        'a move that is not a confirmation' => [TalentBookingStatus::Contacted, TalentBookingStatus::Proposed, false, false, true],
    ]);
});
