<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Models\Events\Venue;

class DeleteAction
{
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
     *
     * @example
     * ```php
     * $venue = Venue::find(1);
     * resolve(DeleteAction::class)->handle($venue);
     * ```
     */
    public function handle(Venue $venue): void
    {
        $venue->delete();
    }
}
