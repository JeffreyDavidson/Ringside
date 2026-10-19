<?php

declare(strict_types=1);

namespace App\Lifecycle\Venues;

use App\Exceptions\Venues\CannotBeDeletedException;
use App\Exceptions\Venues\CannotBeRestoredException;
use App\Exceptions\Venues\CannotBeUpdatedException;
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

        $sharedDay = $this->sharedDay($venue, $venue->timezone);

        if ($sharedDay !== null) {
            throw CannotBeRestoredException::venueDoubleBooked($venue, $sharedDay);
        }
    }

    /**
     * Changing the time zone moves the venue-local calendar days, so two live events on different days in the old zone
     * can land on one day in the new zone. Refuse the change before it double-books the venue.
     */
    public function ensureTimezoneKeepsEventsApart(Venue $venue, string $timezone): void
    {
        $sharedDay = $this->sharedDay($venue, $timezone);

        if ($sharedDay !== null) {
            throw CannotBeUpdatedException::timezoneDoubleBooks($venue, $timezone, $sharedDay);
        }
    }

    /**
     * A venue hosts one event per calendar day (in its time zone). Events restored or created by another route can
     * leave two live events on one day. Returns the first such day in the given zone, or null when there is none.
     */
    private function sharedDay(Venue $venue, string $timezone): ?string
    {
        $seenDays = [];

        /** @var Collection<int, Carbon> $dates */
        $dates = $venue->events()
            ->withoutGlobalScope(PromotionContextScope::class)
            ->whereNotNull('date')
            ->pluck('date');

        foreach ($dates as $date) {
            $day = $date->copy()->setTimezone($timezone)->format('M j, Y');

            if (isset($seenDays[$day])) {
                return $day;
            }

            $seenDays[$day] = true;
        }

        return null;
    }
}
