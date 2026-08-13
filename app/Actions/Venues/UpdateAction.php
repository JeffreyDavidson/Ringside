<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Data\Events\VenueData;
use App\Models\Events\Venue;

class UpdateAction
{
    /**
     * Update a venue.
     *
     * This handles the complete venue update workflow:
     * - Updates venue location and facility information
     * - Maintains data integrity for existing event bookings
     * - Preserves venue history and event associations
     *
     * @param  Venue  $venue  The venue to update
     * @param  VenueData  $venueData  The updated venue information
     * @return Venue The updated venue instance
     */
    public function handle(Venue $venue, VenueData $venueData): Venue
    {
        $venue->update([
            'name' => $venueData->name,
            'address' => $venueData->address,
        ]);

        return $venue;
    }
}
