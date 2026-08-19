<?php

declare(strict_types=1);

namespace App\Exceptions\Events;

use App\Exceptions\BaseBusinessException;
use App\Models\Events\Event;

final class CannotBeRescheduledException extends BaseBusinessException
{
    public static function alreadyOccurred(Event $event): self
    {
        return new self("Event [{$event->name}] cannot be rescheduled because it has already occurred.");
    }
}
