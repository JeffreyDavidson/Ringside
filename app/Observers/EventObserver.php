<?php

namespace App\Observers;

use App\Enums\EventStatus;
use App\Models\Event;

class EventObserver
{
    /**
     * Handle the Event "saving" event.
     *
     * @param  \App\Models\Event  $event
     * @return void
     */
    public function saving(Event $event)
    {
        if ($event->isScheduled()) {
            $event->status = EventStatus::SCHEDULED;
        } elseif ($event->isPast()) {
            $event->status = EventStatus::PAST;
        } else {
            $event->status = EventStatus::UNSCHEDULED;
        }
    }
}
