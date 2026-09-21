<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventData;
use App\Lifecycle\Venues\VenueSchedulingEligibility;
use App\Models\Events\Event;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    public function handle(EventData $eventData): Event
    {
        return DB::transaction(function () use ($eventData): Event {
            $venue = $eventData->venue?->refreshForUpdate();

            if ($venue !== null && $eventData->date instanceof Carbon) {
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
}
