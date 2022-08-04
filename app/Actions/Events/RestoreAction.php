<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Models\Event;
use Lorisleiva\Actions\Concerns\AsAction;

class RestoreAction extends BaseEventAction
{
    use AsAction;

    /**
     * Restore an event.
     *
     * @param  \App\Models\Event  $event
     * @return void
     */
    public function handle(Event $event): void
    {
        $this->eventRepository->restore($event);
    }
}
