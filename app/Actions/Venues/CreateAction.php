<?php

declare(strict_types=1);

namespace App\Actions\Venues;

use App\Data\Events\VenueData;
use App\Models\Events\Venue;

class CreateAction
{
    /**
     * Create a venue.
     *
     * This handles the complete venue creation workflow:
     * - Creates the venue record with location and facility details
     * - Establishes the venue as available for event hosting
     * - Sets up the foundation for future event bookings
     *
     * @param  VenueData  $venueData  The data transfer object containing venue information
     * @return Venue The newly created venue instance
     */
    public function handle(VenueData $venueData): Venue
    {
        return Venue::query()->create([
            'name' => $venueData->name,
            'street_address' => $venueData->street_address,
            'city' => $venueData->city,
            'state' => $venueData->state,
            'zipcode' => $venueData->zipcode,
        ]);
    }
}
