<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
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
    public function create(EventData $eventData): Model
    {
        return Event::create([
            'name' => $eventData->name,
            'date' => $eventData->date,
            'venue_id' => $eventData->venue->id ?? null,
            'preview' => $eventData->preview,
        ]);
    }

    public function update(Event $event, EventData $eventData): Event
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
    public function delete(Event $event): void
    {
        $event->delete();
    }

    /**
     * Restore a given event.
     *
     * @param  \App\Models\Event  $event
     * @return void
     */
    public function restore(Event $event): void
    {
        $event->restore();
    }
}
