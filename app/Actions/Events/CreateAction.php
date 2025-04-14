<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\EventData;
use App\Models\Event;
use Lorisleiva\Actions\Concerns\AsAction;

final class CreateAction extends BaseEventAction
{
    use AsAction;

    /**
     * Create an event.
     */
    public function handle(EventData $eventData): Event
    {
        return $this->eventRepository->create($eventData);
    }
}
