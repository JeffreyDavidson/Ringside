<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeEstablishedException extends BaseBusinessException
{
    public static function deleted(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be established because it is deleted. Restore the stable first.");
    }

    public static function established(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is already established and cannot be re-established.");
    }

    public static function retired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} is retired and cannot be established.");
    }
}
