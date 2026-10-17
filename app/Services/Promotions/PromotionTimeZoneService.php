<?php

declare(strict_types=1);

namespace App\Services\Promotions;

use App\Models\Events\Event;
use App\Models\Promotions\Promotion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;

/**
 * Converts between stored UTC instants and the wall-clock time of a promotion's time zone.
 * Stateless and read-only, so the methods are static; no promotion means the application time zone.
 */
class PromotionTimeZoneService
{
    /** Show a stored (UTC) instant as wall-clock time in the promotion's time zone. */
    public static function toLocalTime(?Promotion $promotion, Carbon $date): Carbon
    {
        return $date->copy()->setTimezone(self::zoneOf($promotion));
    }

    /** Read a wall-clock time entered in the promotion's time zone (a datetime-local value) as the UTC instant to store. */
    public static function parseLocalTime(?Promotion $promotion, string $value): Carbon
    {
        return Date::parse($value, self::zoneOf($promotion))->utc();
    }

    /** Whether a wall-clock time exists in the promotion's zone; the hour skipped when clocks move forward does not. */
    public static function localTimeExists(?Promotion $promotion, string $value): bool
    {
        $format = 'Y-m-d H:i:s';

        return Date::parse($value, self::zoneOf($promotion))->format($format) === Date::parse($value, 'UTC')->format($format);
    }

    public static function zoneOf(?Promotion $promotion): string
    {
        return $promotion instanceof Promotion ? $promotion->timezone : config()->string('app.timezone');
    }

    /**
     * Read a form date entered in the event promotion's time zone as the instant to store. The value the form was
     * prefilled with keeps the stored instant: in the repeated hour of a clock change it would otherwise read as the
     * first occurrence and silently move an event that sits in the second.
     */
    public static function parseEventLocalDate(Event $event, string $value): Carbon
    {
        if ($event->date instanceof Carbon && $event->local_date?->format('Y-m-d\\TH:i') === $value) {
            return $event->date;
        }

        return self::parseLocalTime($event->promotion, $value);
    }
}
