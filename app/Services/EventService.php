<?php

namespace App\Services;

use App\Repositories\EventRepository;

class EventService
{
    /**
     * The repository implementation.
     *
     * @var \App\Repositories\EventRepository
     */
    protected $eventRepository;

    /**
     * Create a new event service instance.
     *
     * @param \App\Repositories\EventRepository $eventRepository
     */
    public function __construct(EventRepository $eventRepository)
    {
        $this->eventRepository = $eventRepository;
    }

    /**
     * Create an event.
     *
     * @param  array $data
     * @return \App\Models\Event $event
     */
    public function create(array $data)
    {
        $event = $this->eventRepository->create($data);

        return $event;
    }
}
