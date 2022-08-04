<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\EventData;
use App\Models\Event;
use Lorisleiva\Actions\Concerns\AsAction;

class UpdateAction extends BaseEventAction
{
    use AsAction;

    /**
     * Update an event.
     *
     * @param  \App\Models\Event  $event
     * @param  \App\Data\EventData  $eventData
     * @return \App\Models\Event
     */
    public function handle(Event $event, EventData $eventData): Event
    {
        return $this->eventRepository->update($event, $eventData);
    }
}
