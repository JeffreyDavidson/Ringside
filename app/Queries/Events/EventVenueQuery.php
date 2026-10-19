<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Models\Events\Event;
use App\Models\Events\Venue;

/** The venue an event is booked at, including a venue that has since been deleted. */
class EventVenueQuery
{
    public function get(Event $event): ?Venue
    {
        if ($event->venue_id === null) {
            return null;
        }

        return $event->venue()->withTrashed()->first();
    }
}
