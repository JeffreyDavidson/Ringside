<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\EventData;
use App\Models\Event;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction extends BaseEventAction
{
    use AsAction;

    /**
     * Create an event.
     *
     * @param  \App\Data\EventData  $eventData
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function handle(EventData $eventData): Event
    {
        return $this->eventRepository->create($eventData);
    }
}
