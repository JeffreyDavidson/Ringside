<?php

declare(strict_types=1);

namespace App\Queries\Events;

use App\Enums\Lifecycle\LifecycleDimension;
use App\Models\Events\Event;

/** The id of the event's latest deletion or restoration, or 0 when it never changed. */
class EventDeletionVersionQuery
{
    public function get(Event $event): int
    {
        return $event->lifecycleTransitions()->where('dimension', LifecycleDimension::Deletion)->orderByDesc('id')->first()->id ?? 0;
    }
}
