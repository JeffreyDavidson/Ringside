<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeRetiredException extends BaseBusinessException
{
    public static function deleted(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be retired because it is deleted. Restore the stable first.");
    }

    public static function notActive(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is not currently active and cannot be retired.");
    }

    public static function alreadyRetired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is already retired.");
    }
}
