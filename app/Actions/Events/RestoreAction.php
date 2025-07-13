<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Events\Event;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction extends BaseEventAction
{
    use AsAction;

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
     *
     * @example
     * ```php
     * $deletedEvent = Event::onlyTrashed()->find(1);
     * RestoreAction::run($deletedEvent);
     * ```
     */
    public function handle(Event $event): void
    {
        DB::transaction(function () use ($event): void {
            $this->eventRepository->restore($event);

            // Note: No automatic match restoration to avoid conflicts.
            // All match relationships must be re-established explicitly using separate actions.
            // Venue booking and promotional data are preserved automatically.
        });
    }
}
