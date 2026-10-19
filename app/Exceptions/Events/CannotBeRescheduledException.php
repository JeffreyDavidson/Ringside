<?php

declare(strict_types=1);

namespace App\Exceptions\Events;

use App\Exceptions\BaseBusinessException;
use App\Models\Events\Event;

final class CannotBeRescheduledException extends BaseBusinessException
{
    public static function hasRecordedResults(Event $event): self
    {
        return new self(__('events.errors.reschedule.has_recorded_results', ['name' => $event->name]));
    }

    public static function hasTitleReigns(Event $event): self
    {
        return new self(__('events.errors.reschedule.has_title_reigns', ['name' => $event->name]));
    }
}
