<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Exceptions\Venues\CannotBeRestoredException;
use App\Lifecycle\Events\EventRosterSchedulingEligibility;
use App\Lifecycle\Events\SchedulingSlotLock;
use App\Lifecycle\Matches\MatchAssignmentEligibility;
use App\Lifecycle\Periods\DeletionStateManager;
use App\Lifecycle\Tournaments\TournamentLocks;
use App\Lifecycle\Tournaments\TournamentMatchRequirements;
use App\Lifecycle\Venues\VenueSchedulingEligibility;
use App\Models\Events\Event;
use App\Models\Tournaments\Tournament;
use Illuminate\Support\Facades\DB;

class RestoreAction
{
    public function __construct(
        private readonly DeletionStateManager $deletionState,
        private readonly MatchAssignmentEligibility $assignmentEligibility,
        private readonly SchedulingSlotLock $slotLock,
        private readonly TournamentLocks $tournamentLocks,
        private readonly TournamentMatchRequirements $tournamentRequirements,
        private readonly EventRosterSchedulingEligibility $rosterAvailability,
    ) {}

    /**
     * Restore a soft-deleted event.
     *
     * This handles the complete event restoration workflow:
     * - Restores the soft-deleted event record
     * - Makes the event available for future scheduling and management
     * - Rejects the restore while the event's venue is soft-deleted; restore the venue first
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

            $tournamentIds = $this->tournamentLocks->forEvent($event->id);

            if ($tournamentIds !== []) {
                $this->tournamentLocks->lockMany($tournamentIds, [$event->id], $event->date);
            }
            $lockedEvent = $event->refreshForUpdate();
            $venue = $lockedEvent->venue()->withTrashed()->first();

            // A deleted venue is invisible to the check below, and its day may have been taken while the event was
            // deleted, so the venue must be restored (which re-checks its days) before the event.
            if ($venue?->trashed()) {
                throw CannotBeRestoredException::venueDeleted($lockedEvent, $venue);
            }

            $venue = $venue?->refreshForUpdate();

            if ($venue !== null && $lockedEvent->date !== null) {
                VenueSchedulingEligibility::ensureAvailable($venue, $lockedEvent->date, $lockedEvent);
            }

            $this->tournamentRequirements->ensureEventChronology($lockedEvent, $lockedEvent->date);
            $this->assignmentEligibility->ensureEventCanBeRescheduled($lockedEvent, $lockedEvent->date);
            Tournament::query()->whereKey($tournamentIds)->increment('version');

            if ($lockedEvent->date?->isFuture()) {
                $this->rosterAvailability->ensureAvailableAt($lockedEvent, $lockedEvent->date, lock: true);
            }
            $this->deletionState->restore($lockedEvent, now());
        }, attempts: 3);
    }
}
