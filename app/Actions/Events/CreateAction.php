<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventData;
use App\Lifecycle\VenueSchedulingEligibility;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    /**
     * Create an event.
     *
     * This handles the complete event creation workflow:
     * - Creates the event record with name, date, venue, and description
     * - Sets initial status based on whether a date is provided
     * - Establishes the event for future match booking and scheduling
     *
     * @param  EventData  $eventData  The data transfer object containing event information
     * @return Event The newly created event instance
     */
    public function handle(EventData $eventData): Event
    {
        return DB::transaction(function () use ($eventData): Event {
            $venue = $this->lockScheduledVenue($eventData);

            if ($venue !== null && $eventData->date !== null) {
                VenueSchedulingEligibility::ensureAvailable($venue, $eventData->date);
            }

            $event = Event::query()->create([
                'name' => $eventData->name,
                'date' => $eventData->date,
                'venue_id' => $eventData->venue?->id,
                'preview' => $eventData->preview,
            ]);

            return $event;
        }, attempts: 3);
    }

    private function lockScheduledVenue(EventData $eventData): ?Venue
    {
        if ($eventData->date === null || $eventData->venue === null) {
            return null;
        }

        return Venue::query()
            ->whereKey($eventData->venue->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
