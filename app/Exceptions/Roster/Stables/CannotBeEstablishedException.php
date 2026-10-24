<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeEstablishedException extends BaseBusinessException
{
    public static function deleted(Stable $stable): static
    {
        return self::forModel('stables.errors.established.deleted', $stable);
    }

    public static function established(Stable $stable): static
    {
        return self::forModel('stables.errors.established.established', $stable);
    }

    public static function insufficientMembers(Stable $stable, int $currentMembers, int $minimumMembers): static
    {
        return self::forModel('stables.errors.established.insufficient_members', $stable, ['current_members' => $currentMembers, 'minimum_members' => $minimumMembers]);
    }

    public static function withEndDate(Stable $stable): static
    {
        return self::forModel('stables.errors.established.with_end_date', $stable);
    }
}
