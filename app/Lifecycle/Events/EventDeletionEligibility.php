<?php

declare(strict_types=1);

namespace App\Lifecycle\Events;

use App\Exceptions\Events\CannotBeDeletedException;
use App\Models\Events\Event;

final class EventDeletionEligibility
{
    /** Deleting an event is refused for the same reason as rescheduling it: its matches created or closed title reigns. */
    public static function ensureCanBeDeleted(Event $event): void
    {
        if (EventSchedulingEligibility::hasTitleReigns($event)) {
            throw CannotBeDeletedException::hasTitleChange($event);
        }
    }
}
