<?php

declare(strict_types=1);

namespace App\Lifecycle\Venues;

use App\Exceptions\Events\CannotBeRestoredException;
use App\Exceptions\Venues\CannotBeDeletedException;
use App\Models\Events\Venue;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class VenueDeletionEligibility
{
    /**
     * A venue with upcoming events cannot be deleted: the promoter must move or delete those events first. "Upcoming"
     * means on or after the start of today in the venue's time zone. Venues are shared, so events of every promotion
     * count, but the exception carries only the number, never another promotion's event details.
     */
    public function ensureCanDelete(Venue $venue): void
    {
        $upcomingEvents = $venue->events()
            ->withoutGlobalScope(PromotionContextScope::class)
            ->where('date', '>=', Carbon::now($venue->timezone)->startOfDay()->utc())
            ->count();

        if ($upcomingEvents > 0) {
            throw CannotBeDeletedException::hasUpcomingEvents($venue, $upcomingEvents);
        }
    }

    public function ensureCanRestore(Venue $venue): void
    {
        if (! $venue->trashed()) {
            throw CannotBeRestoredException::notDeleted($venue);
        }

        $conflictingVenue = Venue::query()
            ->whereName($venue->name)
            ->whereKeyNot($venue->getKey())
            ->first();

        if ($conflictingVenue !== null) {
            throw CannotBeRestoredException::nameConflict($venue, $conflictingVenue->name);
        }

        $this->ensureNoSharedDay($venue);
    }

    /**
     * A venue hosts one event per calendar day (in its time zone). Events restored or created by another route can
     * leave two live events on one day, and restoring the venue would make that visible and bookable again.
     */
    private function ensureNoSharedDay(Venue $venue): void
    {
        $seenDays = [];

        /** @var Collection<int, Carbon> $dates */
        $dates = $venue->events()
            ->withoutGlobalScope(PromotionContextScope::class)
            ->whereNotNull('date')
            ->pluck('date');

        foreach ($dates as $date) {
            $day = $date->copy()->setTimezone($venue->timezone)->format('M j, Y');

            if (isset($seenDays[$day])) {
                throw CannotBeRestoredException::venueDoubleBooked($venue, $day);
            }

            $seenDays[$day] = true;
        }
    }
}
