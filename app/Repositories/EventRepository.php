<?php

namespace App\Repositories;

use App\Models\Event;

class EventRepository
{
    /**
     * Create a new event with the given data.
     *
     * @param  array $data
     * @return \App\Models\Event
     */
    public function create(array $data)
    {
        return Event::create($data);;
    }
}
