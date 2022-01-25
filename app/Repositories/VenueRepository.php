<?php

namespace App\Repositories;

use App\DataTransferObjects\VenueData;
use App\Models\Venue;

class VenueRepository
{
    /**
     * Create a new venue with the given data.
     *
     * @param  \App\DataTransferObjects\VenueData $venueData
     *
     * @return \App\Models\Venue
     */
    public function create(VenueData $venueData)
    {
        return Venue::create([
            'name' => $venueData->name,
            'address1' => $venueData->address1,
            'address2' => $venueData->address2,
            'city' => $venueData->city,
            'state' => $venueData->state,
            'zip' => $venueData->zip,
        ]);
    }

    /**
     * Update the given venue with the given data.
     *
     * @param  \App\Models\Venue $venue
     * @param  \App\DataTransferObjects\VenueData $venueData
     *
     * @return \App\Models\Venue $venue
     */
    public function update(Venue $venue, VenueData $venueData)
    {
        $venue->update([
            'name' => $venueData->name,
            'address1' => $venueData->address1,
            'address2' => $venueData->address2,
            'city' => $venueData->city,
            'state' => $venueData->state,
            'zip' => $venueData->zip,
        ]);

        return $venue;
    }

    /**
     * Delete a given venue.
     *
     * @param  \App\Models\Venue $venue
     *
     * @return void
     */
    public function delete(Venue $venue)
    {
        $venue->delete();
    }

    /**
     * Restore a given venue.
     *
     * @param  \App\Models\Venue $venue
     *
     * @return void
     */
    public function restore(Venue $venue)
    {
        $venue->restore();
    }
}
