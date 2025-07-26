<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Events\Event;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction
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
        $event->restore();
    }
}
