<?php

declare(strict_types=1);

namespace App\Lifecycle;

use App\Enums\EventStatus;
use App\Exceptions\Events\CannotBeRescheduledException;
use App\Models\Events\Event;
use Illuminate\Support\Carbon;

final class EventSchedulingEligibility
{
    public static function ensureDateCanChange(Event $event, ?Carbon $targetDate): void
    {
        if (! self::isDateChanging($event, $targetDate)) {
            return;
        }

        if ($event->status === EventStatus::Past) {
            throw CannotBeRescheduledException::alreadyOccurred($event);
        }
    }

    public static function isDateChanging(Event $event, ?Carbon $targetDate): bool
    {
        if ($event->date === null) {
            return $targetDate !== null;
        }

        return $targetDate === null || ! $event->date->isSameSecond($targetDate);
    }
}
