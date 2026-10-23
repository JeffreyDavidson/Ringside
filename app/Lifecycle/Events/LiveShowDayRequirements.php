<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use App\Enums\Events\LiveShowStatus;
use App\Exceptions\Events\LiveShowException;
use App\Models\Events\EventLiveRun;
use App\Services\Promotions\PromotionTimeZoneService;

/** Attendance and item starts are only available on the event's local day, unless the show is already running. */
final class LiveShowDayRequirements
{
    public function ensureShowDay(EventLiveRun $run): void
    {
        if ($run->status === LiveShowStatus::Running) {
            return;
        }
        $zone = PromotionTimeZoneService::zoneOf($run->event->promotion);

        if ($run->event->date?->copy()->setTimezone($zone)->toDateString() !== now()->setTimezone($zone)->toDateString()) {
            throw LiveShowException::showDay();
        }
    }
}
