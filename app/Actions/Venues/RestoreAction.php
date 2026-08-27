<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Models\Events\Venue;
use App\Services\Venues\VenueDeletionService;
use Illuminate\Support\Carbon;

class RestoreAction
{
    public function __construct(
        private readonly VenueDeletionService $deletion,
    ) {}

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
     * @param  Carbon|null  $restoreDate  The restoration date (defaults to now)
     */
    public function handle(Venue $venue, ?Carbon $restoreDate = null): void
    {
        $this->deletion->restore($venue, $restoreDate ?? now());
    }
}
