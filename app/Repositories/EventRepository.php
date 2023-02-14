<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\EventData;
use App\Models\Event;
use Illuminate\Database\Eloquent\Model;

class EventRepository
{
    /**
     * Create a new event with the given data.
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
     */
    public function delete(Event $event): void
    {
        $event->delete();
    }

    /**
     * Restore a given event.
     */
    public function restore(Event $event): void
    {
        $event->restore();
    }
}
