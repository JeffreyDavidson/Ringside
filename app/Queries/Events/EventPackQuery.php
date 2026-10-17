<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Models\Events\Event;
use App\Models\Events\EventPack;

/** The saved pack of an event, or null while none has been saved. */
class EventPackQuery
{
    public function get(Event $event): ?EventPack
    {
        return $event->pack()->first();
    }
}
