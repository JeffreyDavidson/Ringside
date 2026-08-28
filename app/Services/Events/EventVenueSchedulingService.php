<?php

declare(strict_types=1);

namespace App\Services\Events;

use App\Lifecycle\Venues\VenueSchedulingEligibility;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Support\Carbon;

final class EventVenueSchedulingService
{
    public function schedule(?Carbon $date, ?Venue $venue, ?Event $event = null): ?Venue
    {
        $lockedVenue = $this->lockScheduledVenue($date, $venue);
        $this->ensureAvailable($lockedVenue, $date, $event);

        return $lockedVenue;
    }

    public function lockScheduledVenue(?Carbon $date, ?Venue $venue): ?Venue
    {
        if ($date === null || $venue === null) {
            return null;
        }

        return $venue->refreshForUpdate();
    }

    public function ensureAvailable(?Venue $venue, ?Carbon $date, ?Event $event = null): void
    {
        if ($venue === null || $date === null) {
            return;
        }

        VenueSchedulingEligibility::ensureAvailable($venue, $date, $event);
    }
}
