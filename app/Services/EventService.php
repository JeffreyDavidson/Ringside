<?php

namespace App\Services;

use App\Models\Event;
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
     * Create an event with given data.
     *
     * @param  array $data
     * @return \App\Models\Event
     */
    public function create(array $data)
    {
        return $this->eventRepository->create($data);
    }

    /**
     * Update a given event.
     *
     * @param  \App\Models\Event $event
     * @param  array $data
     * @return \App\Models\Event $event
     */
    public function update(Event $event, array $data)
    {
        return $this->eventRepository->update($event, $data);
    }

    /**
     * Delete an event.
     *
     * @param  \App\Models\Event $event
     * @return void
     */
    public function delete($event)
    {
        $this->eventRepository->delete($event);
    }

    /**
     * Restore an event.
     *
     * @param  \App\Models\Event $event
     * @return void
     */
    public function restore(Event $event)
    {
        $this->eventRepository->restore($event);
    }
}
