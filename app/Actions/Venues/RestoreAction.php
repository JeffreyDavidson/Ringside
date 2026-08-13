<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Lifecycle\DeletionStateManager;
use App\Models\Events\Venue;

class RestoreAction
{
    public function __construct(private readonly DeletionStateManager $deletionState) {}

    /**
     * Restore a soft-deleted venue.
     *
     * This handles the complete venue restoration workflow:
     * - Restores the soft-deleted venue record
     * - Makes the venue available for event hosting again
     * - Preserves all event history and associations
     * - Reactivates the venue for future bookings
     *
     * @param  Venue  $venue  The soft-deleted venue to restore
     */
    public function handle(Venue $venue): void
    {
        $this->deletionState->restore($venue, now());
    }
}
