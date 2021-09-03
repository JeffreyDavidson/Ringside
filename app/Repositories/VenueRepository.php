<?php

namespace App\Repositories;

use App\Models\Venue;

class VenueRepository
{
    /**
     * Create a new venue with the given data.
     *
     * @param  array $data
     * @return \App\Models\Venue
     */
    public function create(array $data)
    {
        return Venue::create($data);
    }

    /**
     * Update the given venue with the given data.
     *
     * @param  \App\Models\Venue $venue
     * @param  array $data
     * @return \App\Models\Venue $venue
     */
    public function update(Venue $venue, array $data)
    {
        return $venue->update([
            'name' => $data['name'],
            'address1' => $data['address1'],
            'address2' => $data['address2'],
            'city' => $data['city'],
            'state' => $data['state'],
            'zip' => $data['zip'],
        ]);
    }

    /**
     * Delete a given venue.
     *
     * @param  \App\Models\Venue $venue
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
     * @return void
     */
    public function restore(Venue $venue)
    {
        $venue->restore();
    }
}
