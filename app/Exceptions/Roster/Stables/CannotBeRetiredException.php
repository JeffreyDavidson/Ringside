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

        return new self(__('stables.errors.retired.deleted', ['context' => $context]));
    }

    public static function notActive(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.retired.not_active', ['context' => $context]));
    }

    public static function alreadyRetired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.retired.already_retired', ['context' => $context]));
    }
}
