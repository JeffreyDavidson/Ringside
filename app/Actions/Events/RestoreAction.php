<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Lifecycle\Periods\DeletionStateManager;
use App\Lifecycle\Venues\VenueSchedulingEligibility;
use App\Models\Events\Event;
use Illuminate\Support\Facades\DB;

class RestoreAction
{
    public function __construct(private readonly DeletionStateManager $deletionState) {}

    /**
     * Restore a soft-deleted event.
     *
     * This handles the complete event restoration workflow:
     * - Restores the soft-deleted event record
     * - Makes the event available for future scheduling and management
     * - Preserves all associated matches, booking history, and promotional data
     * - Does not automatically restore associated matches (if they were also deleted)
     * - Requires separate match restoration actions if matches were deleted
     * - Reactivates event for venue booking and promotional activities
     *
     * @param  Event  $event  The soft-deleted event to restore
     */
    public function handle(Event $event): void
    {
        DB::transaction(function () use ($event): void {
            $lockedEvent = $event->refreshForUpdate();
            $venue = $lockedEvent->venue?->refreshForUpdate();

            if ($venue !== null && $lockedEvent->date !== null) {
                VenueSchedulingEligibility::ensureAvailable($venue, $lockedEvent->date, $lockedEvent);
            }
            $this->deletionState->restore($lockedEvent, now());
        });
    }
}
