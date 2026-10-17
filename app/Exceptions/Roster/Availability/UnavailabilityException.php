<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Availability;

use App\Exceptions\BaseBusinessException;

final class UnavailabilityException extends BaseBusinessException
{
    public static function duplicate(): self
    {
        return new self(__('availability.duplicate'));
    }

    public static function unowned(): self
    {
        return new self(__('availability.unowned'));
    }
}
