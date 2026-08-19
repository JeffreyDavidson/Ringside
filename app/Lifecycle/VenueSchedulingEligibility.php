<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Support\Carbon;

final class VenueSchedulingEligibility
{
    public static function ensureAvailable(Venue $venue, Carbon $date, ?Event $except = null): void
    {
        $events = $venue->events()->where('date', $date);

        if ($except !== null) {
            $events->whereKeyNot($except->getKey());
        }

        if ($events->exists()) {
            throw SchedulingConflictException::venueAlreadyBooked($venue->name);
        }
    }
}
