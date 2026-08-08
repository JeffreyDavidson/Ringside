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
     *
     * @example
     * ```php
     * $venueData = new VenueData([
     *     'name' => 'Updated Arena Name',
     *     'street_address' => 'New Address'
     * ]);
     * $updatedVenue = resolve(UpdateAction::class)->handle($venue, $venueData);
     * ```
     */
    public function handle(Venue $venue, VenueData $venueData): Venue
    {
        $venue->update([
            'name' => $venueData->name,
            'street_address' => $venueData->street_address,
            'city' => $venueData->city,
            'state' => $venueData->state,
            'zipcode' => $venueData->zipcode,
        ]);

        return $venue;
    }
}
