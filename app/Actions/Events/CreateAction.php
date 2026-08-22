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
