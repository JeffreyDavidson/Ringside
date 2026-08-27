<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\Events\EventData;
use App\Models\Events\Event;
use App\Services\Events\EventVenueSchedulingService;
use Illuminate\Support\Facades\DB;

class CreateAction
{
    public function __construct(private readonly EventVenueSchedulingService $venueScheduling) {}

    public function handle(EventData $eventData): Event
    {
        return DB::transaction(function () use ($eventData): Event {
            $this->venueScheduling->schedule($eventData->date, $eventData->venue);

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
