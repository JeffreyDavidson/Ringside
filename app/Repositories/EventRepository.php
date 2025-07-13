<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Data\Events\EventData;
use App\Models\Events\Event;
use App\Repositories\Contracts\EventRepositoryInterface;
use App\Repositories\Support\BaseRepository;
use Tests\Unit\Repositories\EventRepositoryTest;

/**
 * Repository for Event model business operations and data persistence.
 *
 * Handles all event related database operations including CRUD operations
 * and event management functionality.
 *
 * @see EventRepositoryTest
 */
class EventRepository extends BaseRepository implements EventRepositoryInterface
{
    /**
     * Create a new event.
     */
    public function create(EventData $eventData): Event
    {
        return Event::query()->create([
            'name' => $eventData->name,
            'date' => $eventData->date,
            'venue_id' => $eventData->venue->id ?? null,
            'preview' => $eventData->preview,
        ]);
    }

    /**
     * Update an event.
     */
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
     * Restore a soft-deleted event.
     */
    public function restore(Event $event): void
    {
        $event->restore();
    }
}
