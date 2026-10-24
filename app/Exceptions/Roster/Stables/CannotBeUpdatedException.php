<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeUpdatedException extends BaseBusinessException
{
    public static function startDateLocked(Stable $stable): static
    {
        return self::forModel('stables.errors.updated.start_date_locked', $stable);
    }

    public static function endsOpenPeriod(Stable $stable): static
    {
        return self::forModel('stables.errors.updated.ends_open_period', $stable);
    }

    public static function startsAfterMembership(Stable $stable): static
    {
        return self::forModel('stables.errors.updated.starts_after_membership', $stable);
    }

    public static function endsBeforeMembership(Stable $stable): static
    {
        return self::forModel('stables.errors.updated.ends_before_membership', $stable);
    }

    public static function inactiveWithMembers(Stable $stable): static
    {
        return self::forModel('stables.errors.updated.inactive_with_members', $stable);
    }

    public static function nameTaken(string $name): static
    {
        return self::forReason(BusinessRuleReason::NameTaken, __('stables.errors.updated.name_taken', ['name' => $name]));
    }
}
