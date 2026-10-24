<?php

declare(strict_types=1);

namespace App\Exceptions\Venues;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Events\Event;
use App\Models\Events\Venue;

final class CannotBeRestoredException extends BaseBusinessException
{
    public static function notDeleted(Venue $venue): static
    {
        return self::forModel('venues.errors.restored.not_deleted', $venue, reason: BusinessRuleReason::NotDeleted);
    }

    public static function nameConflict(Venue $venue, string $conflictingName): static
    {
        return self::forModel('venues.errors.restored.name_conflict', $venue, ['conflicting_name' => $conflictingName]);
    }

    public static function venueDeleted(Event $event, Venue $venue): static
    {
        return self::forModel('events.errors.restored.venue_deleted', $event, ['venue' => $venue->name]);
    }

    public static function venueDoubleBooked(Venue $venue, string $venueLocalDate): static
    {
        return self::forModel('venues.errors.restored.double_booked', $venue, ['date' => $venueLocalDate]);
    }
}
