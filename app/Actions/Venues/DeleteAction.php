<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Lifecycle\Periods\DeletionStateManager;
use App\Models\Events\Venue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeleteAction
{
    public function __construct(private readonly DeletionStateManager $deletionState) {}

    /**
     * Delete a venue.
     *
     * This handles the complete venue deletion workflow:
     * - Soft deletes the venue record to preserve historical data
     * - Maintains referential integrity with associated events
     * - Preserves venue history for past events and reporting
     * - Allows for future restoration if needed
     *
     * @param  Venue  $venue  The venue to delete
     * @param  Carbon|null  $deletionDate  The deletion date (defaults to now)
     */
    public function handle(Venue $venue, ?Carbon $deletionDate = null): void
    {
        DB::transaction(function () use ($venue, $deletionDate): void {
            $lockedVenue = $venue->refreshForUpdate();

            $this->deletionState->delete($lockedVenue, $deletionDate ?? now());
        });
    }
}
