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
        return Event::create([
            'name' => $data['name'],
            'date' => $data['date'],
            'venue_id' => $data['venue_id'],
            'preview' => $data['preview'],
        ]);
    }

    /**
     * Update a given event with given data.
     *
     * @param  \App\Models\Event $event
     * @param  array $data
     * @return \App\Models\Event $event
     */
    public function update(Event $event, array $data)
    {
        return $event->update([
            'name' => $data['name'],
            'date' => $data['date'],
            'venue_id' => $data['venue_id'],
            'preview' => $data['preview'],
        ]);
    }

    /**
     * Delete a given event.
     *
     * @param  \App\Models\Event $event
     * @return void
     */
    public function delete(Event $event)
    {
        $event->delete($event);
    }

    /**
     * Restore a given event.
     *
     * @param  \App\Models\Event $event
     * @return void
     */
    public function restore(Event $event)
    {
        $event->restore($event);
    }

    /**
     * Restore a given event.
     *
     * @param  \App\Models\Event $event
     * @return void
     */
    public function addMatch(Event $event, $matches)
    {
        foreach ($matches as $match) {
            $event->matches()->create([

            ]);
        }
    }
}
