<?php

declare(strict_types=1);

namespace App\Services;

use App\Lifecycle\VenueSchedulingEligibility;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use Illuminate\Support\Carbon;

final class EventVenueSchedulingService
{
    public function lockScheduledVenue(?Carbon $date, ?Venue $venue): ?Venue
    {
        if ($date === null || $venue === null) {
            return null;
        }

        return Venue::query()
            ->whereKey($venue->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function ensureAvailable(?Venue $venue, ?Carbon $date, ?Event $event = null): void
    {
        if ($venue === null || $date === null) {
            return;
        }

        VenueSchedulingEligibility::ensureAvailable($venue, $date, $event);
    }
}
