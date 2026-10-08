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

        return new self(__('stables.errors.disbanded.deleted', ['context' => $context]));
    }

    public static function unactivated(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.disbanded.unactivated', ['context' => $context]));
    }

    public static function disbanded(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.disbanded.disbanded', ['context' => $context]));
    }

    public static function hasFutureActivation(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.disbanded.has_future_activation', ['context' => $context]));
    }
}
