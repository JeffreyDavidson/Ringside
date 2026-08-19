<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Lifecycle\DeletionStateManager;
use App\Models\Events\Venue;
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
     */
    public function handle(Venue $venue): void
    {
        DB::transaction(function () use ($venue): void {
            $lockedVenue = Venue::query()
                ->whereKey($venue->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $this->deletionState->delete($lockedVenue, now());
        });
    }
}
