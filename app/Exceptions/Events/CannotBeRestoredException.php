<?php

declare(strict_types=1);

namespace App\Exceptions\Events;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Events\Event;
use App\Models\Events\Venue;

final class CannotBeRestoredException extends BaseBusinessException
{
    public static function notDeleted(Venue $venue): static
    {
        $context = self::formatModelContext($venue);

        return self::forReason(BusinessRuleReason::NotDeleted, __('venues.errors.restored.not_deleted', ['context' => $context]));
    }

    public static function nameConflict(Venue $venue, string $conflictingName): static
    {
        $context = self::formatModelContext($venue);

        return new self(__('venues.errors.restored.name_conflict', ['context' => $context, 'conflicting_name' => $conflictingName]));
    }

    public static function venueDeleted(Event $event, Venue $venue): static
    {
        return new self(__('events.errors.restored.venue_deleted', ['context' => self::formatModelContext($event), 'venue' => $venue->name]));
    }

    public static function venueDoubleBooked(Venue $venue, string $venueLocalDate): static
    {
        return new self(__('venues.errors.restored.double_booked', ['context' => self::formatModelContext($venue), 'date' => $venueLocalDate]));
    }
}
