<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Models\Events\Event;
use App\Models\Events\EventTalentBooking;
use Illuminate\Database\Eloquent\Collection;

/** Every talent booking tracked for an event, oldest first. */
class EventTalentBookingsQuery
{
    /** @return Collection<int, EventTalentBooking> */
    public function get(Event $event): Collection
    {
        return $event->talentBookings()->orderBy('id')->get();
    }
}
