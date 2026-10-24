<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeRetiredException extends BaseBusinessException
{
    public static function deleted(Stable $stable): static
    {
        return self::forModel('stables.errors.retired.deleted', $stable);
    }

    public static function notActive(Stable $stable): static
    {
        return self::forModel('stables.errors.retired.not_active', $stable);
    }

    public static function alreadyRetired(Stable $stable): static
    {
        return self::forModel('stables.errors.retired.already_retired', $stable);
    }
}
