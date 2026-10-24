<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeDisbandedException extends BaseBusinessException
{
    public static function deleted(Stable $stable): static
    {
        return self::forModel('stables.errors.disbanded.deleted', $stable);
    }

    public static function unactivated(Stable $stable): static
    {
        return self::forModel('stables.errors.disbanded.unactivated', $stable);
    }

    public static function disbanded(Stable $stable): static
    {
        return self::forModel('stables.errors.disbanded.disbanded', $stable);
    }

    public static function hasFutureActivation(Stable $stable): static
    {
        return self::forModel('stables.errors.disbanded.has_future_activation', $stable);
    }
}
