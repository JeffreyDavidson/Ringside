<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Lifecycle\Events\SchedulingSlotLock;
use App\Lifecycle\Periods\DeletionStateManager;
use App\Lifecycle\Venues\VenueSchedulingEligibility;
use App\Models\Events\Event;
use App\Services\Matches\MatchAssignmentConflictService;
use Illuminate\Support\Facades\DB;

class RestoreAction
{
    public function __construct(
        private readonly DeletionStateManager $deletionState,
        private readonly MatchAssignmentConflictService $assignmentConflicts,
        private readonly SchedulingSlotLock $slotLock,
    ) {}

    /**
     * Restore a soft-deleted event.
     *
     * This handles the complete event restoration workflow:
     * - Restores the soft-deleted event record
     * - Makes the event available for future scheduling and management
     * - Rejects the restore when a wrestler, tag team, referee, or title booked on the event's matches is booked
     *   in another event at the same date and time, as rescheduling the event to that date would
     * - Preserves all associated matches, booking history, and promotional data
     * - Does not automatically restore associated matches (if they were also deleted); deleted matches stay
     *   deleted and are not checked for conflicts
     * - Requires separate match restoration actions if matches were deleted
     * - Reactivates event for venue booking and promotional activities
     *
     * @param  Event  $event  The soft-deleted event to restore
     */
    public function handle(Event $event): void
    {
        DB::transaction(function () use ($event): void {
            // The slot lock comes before every event row lock, exactly as in Events\UpdateAction, so the restore
            // queues with concurrent reschedules and restores into the same slot. Bookings never wait for it.
            $this->slotLock->lock($event->date);

            $lockedEvent = $event->refreshForUpdate();
            $venue = $lockedEvent->venue?->refreshForUpdate();

            if ($venue !== null && $lockedEvent->date !== null) {
                VenueSchedulingEligibility::ensureAvailable($venue, $lockedEvent->date, $lockedEvent);
            }

            $this->assignmentConflicts->ensureEventCanBeRescheduled($lockedEvent, $lockedEvent->date);
            $this->deletionState->restore($lockedEvent, now());
        }, attempts: 3);
    }
}
