<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Models\Events\Venue;
use App\Services\VenueDeletionService;
use Illuminate\Support\Carbon;

class DeleteAction
{
    public function __construct(private readonly VenueDeletionService $deletion) {}

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
        $this->deletion->delete($venue, $deletionDate ?? now());
    }
}
