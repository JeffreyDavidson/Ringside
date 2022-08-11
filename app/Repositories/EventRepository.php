<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\EventData;
use App\Models\Event;

class EventRepository
{
    /**
     * Create a new event with the given data.
     *
     * @param  \App\Data\EventData  $eventData
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function create(EventData $eventData)
    {
        return Event::create([
            'name' => $eventData->name,
            'date' => $eventData->date,
            'venue_id' => $eventData->venue->id ?? null,
            'preview' => $eventData->preview,
        ]);
    }

    /**
     * Update a given event with given data.
     *
     * @param  \App\Models\Event  $event
     * @param  \App\Data\EventData  $eventData
     * @return \App\Models\Event
     */
    public function update(Event $event, EventData $eventData)
    {
        $event->update([
            'name' => $eventData->name,
            'date' => $eventData->date,
            'venue_id' => $eventData->venue->id ?? null,
            'preview' => $eventData->preview,
        ]);

        return $event;
    }

    /**
     * Delete a given event.
     *
     * @param  \App\Models\Event  $event
     * @return void
     */
    public function delete(Event $event)
    {
        $event->delete();
    }

    /**
     * Restore a given event.
     *
     * @param  \App\Models\Event  $event
     * @return void
     */
    public function restore(Event $event)
    {
        $event->restore();
    }
}
