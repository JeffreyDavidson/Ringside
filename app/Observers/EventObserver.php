<?php

namespace App\Observers;

use App\Models\Event;
use App\Enums\EventStatus;

class EventObserver
{
    /**
     * Handle the Event "saving" event.
     *
     * @param  App\Models\Event  $event
     * @return void
     */
    public function saving(Event $event)
    {
        if ($event->isScheduled()) {
            $event->status = EventStatus::SCHEDULED;
        } elseif ($event->isPast()) {
            $event->status = EventStatus::PAST;
        } else {
            $event->status = EventStatus::PENDING;
        }
    }
}
