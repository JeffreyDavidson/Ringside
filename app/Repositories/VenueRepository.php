<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\VenueData;
use App\Models\Venue;

class VenueRepository
{
    /**
     * Create a new venue with the given data.
     */
    public function create(VenueData $venueData): Venue
    {
        return Venue::create([
            'name' => $venueData->name,
            'street_address' => $venueData->street_address,
            'city' => $venueData->city,
            'state' => $venueData->state,
            'zip' => $venueData->zip,
        ]);
    }

    /**
     * Update the given venue with the given data.
     *
     * @return \App\Models\Venue $venue
     */
    public function update(Venue $venue, VenueData $venueData): Venue
    {
        $venue->update([
            'name' => $venueData->name,
            'street_address' => $venueData->street_address,
            'city' => $venueData->city,
            'state' => $venueData->state,
            'zip' => $venueData->zip,
        ]);

        return $venue;
    }

    /**
     * Delete a given venue.
     */
    public function delete(Venue $venue): void
    {
        $venue->delete();
    }

    /**
     * Restore a given venue.
     */
    public function restore(Venue $venue): void
    {
        $venue->restore();
    }
}
