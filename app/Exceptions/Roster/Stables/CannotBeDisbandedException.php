<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeDisbandedException extends BaseBusinessException
{
    public static function deleted(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be disbanded because it is deleted. Restore the stable first.");
    }

    public static function unactivated(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is not active and cannot be disbanded.");
    }

    public static function disbanded(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} is already disbanded.");
    }

    public static function retired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} is retired and cannot be disbanded.");
    }

    public static function hasFutureActivation(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} has not been officially activated and cannot be disbanded.");
    }
}
