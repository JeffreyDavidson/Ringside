<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Event;
use Lorisleiva\Actions\Concerns\AsAction;

class DeleteAction extends BaseEventAction
{
    use AsAction;

    /**
     * Delete an event.
     *
     * @param  \App\Models\Event  $event
     * @return void
     */
    public function handle(Event $event): void
    {
        $this->eventRepository->delete($event);
    }
}
