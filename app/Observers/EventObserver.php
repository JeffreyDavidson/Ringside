<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\EventStatus;
use App\Models\Event;

class EventObserver
{
    /**
     * Handle the Event "saved" event.
     *
     * @param  \App\Models\Event $event
     *
     * @return void
     */
    public function saving(Event $event)
    {
        $event->status = match (true) {
            $event->isScheduled() => EventStatus::scheduled(),
            $event->isPast() => EventStatus::past(),
            default => EventStatus::unscheduled()
        };
    }
}
