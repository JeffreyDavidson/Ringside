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
}
