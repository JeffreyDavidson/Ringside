<?php

declare(strict_types=1);

namespace App\Exceptions\Events;

use App\Exceptions\BaseBusinessException;
use App\Models\Events\Event;

final class CannotBeDeletedException extends BaseBusinessException
{
    public static function hasTitleChange(Event $event): self
    {
        return new self(__('events.errors.deleted.has_title_change', ['name' => $event->name]));
    }
}
