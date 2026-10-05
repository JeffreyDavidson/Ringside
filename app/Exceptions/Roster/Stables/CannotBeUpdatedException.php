<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeUpdatedException extends BaseBusinessException
{
    public static function startDateLocked(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} has been active in more than one period, so its start date cannot be changed.");
    }

    public static function endsOpenPeriod(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is active, so it cannot be given an end date here. Disband it instead.");
    }

    public static function inactiveWithMembers(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is disbanded and cannot have members. Reunite it instead.");
    }
}
