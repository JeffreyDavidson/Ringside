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
        if ($event->is_scheduled) {
            $event->status = EventStatus::SCHEDULED;
        } else {
            $event->status = EventStatus::PAST;
        }
    }
}
