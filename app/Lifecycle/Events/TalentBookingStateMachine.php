<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use App\Enums\Events\TalentBookingStatus;
use App\Exceptions\Events\TalentBookingException;
use App\Models\Events\EventTalentBooking;

/**
 * The moves a talent booking may make between its four stored statuses.
 *
 * It reads the booking and refuses with TalentBookingException; it never locks or writes. SaveTalentBookingAction
 * locks the booking and saves it. A person with no booking yet starts from Proposed.
 */
final readonly class TalentBookingStateMachine
{
    /**
     * The allowed moves, by the status the booking is in (the key is the status value). Anything not listed is refused.
     * A booking may be saved again without changing status, and may move between the three open statuses either way.
     * A cancelled booking can only be reopened, as Proposed.
     */
    private const array MOVES = [
        'proposed' => [TalentBookingStatus::Proposed, TalentBookingStatus::Contacted, TalentBookingStatus::Confirmed, TalentBookingStatus::Cancelled],
        'contacted' => [TalentBookingStatus::Proposed, TalentBookingStatus::Contacted, TalentBookingStatus::Confirmed, TalentBookingStatus::Cancelled],
        'confirmed' => [TalentBookingStatus::Proposed, TalentBookingStatus::Contacted, TalentBookingStatus::Confirmed, TalentBookingStatus::Cancelled],
        'cancelled' => [TalentBookingStatus::Proposed],
    ];

    public function canMove(TalentBookingStatus $from, TalentBookingStatus $to): bool
    {
        return in_array($to, self::MOVES[$from->value], true);
    }

    /** Cancelling, and taking back a confirmation (Confirmed to Proposed or Contacted), must say why. */
    public function requiresReason(TalentBookingStatus $from, TalentBookingStatus $to): bool
    {
        return $to === TalentBookingStatus::Cancelled
            || ($from === TalentBookingStatus::Confirmed && in_array($to, [TalentBookingStatus::Proposed, TalentBookingStatus::Contacted], true));
    }

    /**
     * Refuse a move the table does not allow, then one that needs a reason and has none.
     *
     * @throws TalentBookingException
     */
    public function ensureCanMove(?EventTalentBooking $booking, TalentBookingStatus $to, ?string $reason): void
    {
        $from = $booking->status ?? TalentBookingStatus::Proposed;

        if (! $this->canMove($from, $to)) {
            throw TalentBookingException::reopen();
        }

        if ($this->requiresReason($from, $to) && blank($reason)) {
            throw TalentBookingException::reasonRequired();
        }
    }

    /**
     * Refuse a confirmation on an event with no date, or one the person has not been recorded as agreeing to.
     * Saving an already confirmed booking again needs no new agreement.
     *
     * @throws TalentBookingException
     */
    public function ensureCanConfirm(?EventTalentBooking $booking, TalentBookingStatus $to, bool $eventIsScheduled, bool $agreementRecorded): void
    {
        if ($to !== TalentBookingStatus::Confirmed) {
            return;
        }

        if (! $eventIsScheduled || ($booking?->status !== TalentBookingStatus::Confirmed && ! $agreementRecorded)) {
            throw TalentBookingException::confirm();
        }
    }
}
