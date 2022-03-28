<?php

namespace App\Repositories;

use App\Models\Event;

class EventRepository
{
    /**
     * Create a new event with the given data.
     *
     * @param  array $eventData
     *
     * @return \App\Models\Event
     */
    public function create(array $eventData)
    {
        return Event::create([
            'name' => $eventData['name'],
            'date' => $eventData['date'],
            'venue_id' => $eventData['venue_id'],
            'preview' => $eventData['preview'],
        ]);
    }

    /**
     * Update a given event with given data.
     *
     * @param  \App\Models\Event $event
     * @param  array $eventData
     *
     * @return \App\Models\Event
     */
    public function update(Event $event, array $eventData)
    {
        $event->update([
            'name' => $eventData['name'],
            'date' => $eventData['date'],
            'venue_id' => $eventData['venue_id'],
            'preview' => $eventData['preview'],
        ]);

        return $event;
    }

    /**
     * Delete a given event.
     *
     * @param  \App\Models\Event $event
     *
     * @return void
     */
    public function delete(Event $event)
    {
        $event->delete();
    }

    /**
     * Restore a given event.
     *
     * @param  \App\Models\Event $event
     *
     * @return void
     */
    public function restore(Event $event)
    {
        $event->restore();
    }
}
