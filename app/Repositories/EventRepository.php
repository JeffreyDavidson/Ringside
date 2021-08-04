<?php

namespace App\Repositories;

use App\Models\Event;

class EventRepository
{
    /**
     * @param  array $data
     * @return \App\Models\Event
     */
    public function create($data)
    {
        return Event::create($data);;
    }
}
