<?php

declare(strict_types=1);

namespace App\Lifecycle\Venues;

use App\Exceptions\Scheduling\SchedulingConflictException;
use App\Models\Events\Event;
use App\Models\Events\Venue;
use App\Models\Scopes\PromotionContextScope;
use Illuminate\Support\Carbon;

final class VenueSchedulingEligibility
{
    /**
     * Venues are shared by every promotion, so a venue may host only one event per calendar day (in the
     * venue's time zone) across all promotions. Soft-deleted events do not hold a venue.
     */
    public static function ensureAvailable(Venue $venue, Carbon $date, ?Event $except = null): void
    {
        $day = self::calendarDay($venue, $date);

        $events = $venue->events()
            ->withoutGlobalScope(PromotionContextScope::class)
            ->whereBetween('date', [
                $day->copy()->startOfDay()->utc(),
                $day->copy()->endOfDay()->utc(),
            ]);

        if ($except instanceof Event) {
            $events->whereKeyNot($except->getKey());
        }

        if ($events->exists()) {
            throw SchedulingConflictException::venueAlreadyBooked($venue->name, $day->format('M j, Y'));
        }
    }

    /**
     * An event that keeps its venue and calendar day takes no new booking, so an update re-checks the venue only
     * when one of them changes. Events that already share a venue day therefore stay editable.
     */
    public static function isBookingChanging(Event $event, Venue $venue, Carbon $date): bool
    {
        if (! $event->venue()->is($venue) || $event->date === null) {
            return true;
        }

        return self::calendarDay($venue, $event->date)->toDateString() !== self::calendarDay($venue, $date)->toDateString();
    }

    private static function calendarDay(Venue $venue, Carbon $date): Carbon
    {
        return $date->copy()->setTimezone($venue->timezone);
    }
}
