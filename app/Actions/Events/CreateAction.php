<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Data\EventData;
use Illuminate\Database\Eloquent\Model;
use Lorisleiva\Actions\Concerns\AsAction;

class CreateAction extends BaseEventAction
{
    use AsAction;

    /**
     * Create an event.
     */
    public function handle(EventData $eventData): Model
    {
        return $this->eventRepository->create($eventData);
    }
}
