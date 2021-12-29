<?php

namespace App\Services;

use App\DataTransferObjects\EventData;
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
     * @param  \App\DataTransferObjects\EventData $eventData
     * @return \App\Models\Event
     */
    public function create(EventData $eventData)
    {
        return $this->eventRepository->create($eventData);
    }

    /**
     * Update a given event.
     *
     * @param  \App\Models\Event $event
     * @param  array $data
     * @return \App\Models\Event $event
     */
    public function update(Event $event, EventData $eventData)
    {
        return $this->eventRepository->update($event, $eventData);
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
