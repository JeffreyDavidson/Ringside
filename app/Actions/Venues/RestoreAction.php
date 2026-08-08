<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Models\Events\Venue;

class RestoreAction
{
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
        $venue->restore();
    }
}
