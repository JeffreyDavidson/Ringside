<?php

namespace App\Repositories;

use App\Models\Venue;

class VenueRepository
{
    /**
     * @param  array $data
     * @return \App\Models\Venue
     */
    public function create($data)
    {
        return Venue::create($data);;
    }
}
