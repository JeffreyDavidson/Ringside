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
            $event->isScheduled() => EventStatus::Scheduled->value,
            $event->isPast() => EventStatus::Past->value,
            default => EventStatus::Unscheduled->value
        };
    }
}
