<?php

declare(strict_types=1);

namespace App\Exceptions\Venues;

use App\Exceptions\BaseBusinessException;
use App\Models\Events\Venue;

final class CannotBeUpdatedException extends BaseBusinessException
{
    public static function timezoneDoubleBooks(Venue $venue, string $timezone, string $venueLocalDate): static
    {
        return self::forModel('venues.errors.updated.timezone_double_books', $venue, [
            'timezone' => $timezone,
            'date' => $venueLocalDate,
        ]);
    }
}
