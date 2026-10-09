<?php

declare(strict_types=1);

namespace App\Exceptions\Venues;

use App\Exceptions\BaseBusinessException;
use App\Models\Events\Venue;

final class CannotBeDeletedException extends BaseBusinessException
{
    public static function hasUpcomingEvents(Venue $venue, int $eventCount): static
    {
        return new self(trans_choice('venues.errors.deleted.has_upcoming_events', $eventCount, ['context' => self::formatModelContext($venue)]));
    }
}
