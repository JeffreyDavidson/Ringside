<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Repositories\EventRepository;

abstract class BaseEventAction
{
    protected EventRepository $eventRepository;

    /**
     * Create a new base event action instance.
     *
     * @param  \App\Repositories\EventRepository  $eventRepository
     */
    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }
}
