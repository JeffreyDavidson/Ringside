<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\EventStatus;
use App\Models\Event;

class EventObserver
{
    /**
     * Handle the Event "saved" event.
     */
    public function saving(Event $event): void
    {
        $event->status = match (true) {
            $event->isScheduled() => EventStatus::Scheduled,
            $event->isPast() => EventStatus::Past,
            default => EventStatus::Unscheduled
        };
    }
}
