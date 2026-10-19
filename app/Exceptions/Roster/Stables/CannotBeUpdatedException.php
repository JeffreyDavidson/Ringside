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
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.updated.start_date_locked', ['context' => $context]));
    }

    public static function endsOpenPeriod(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.updated.ends_open_period', ['context' => $context]));
    }

    public static function startsAfterMembership(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.updated.starts_after_membership', ['context' => $context]));
    }

    public static function endsBeforeMembership(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.updated.ends_before_membership', ['context' => $context]));
    }

    public static function inactiveWithMembers(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.updated.inactive_with_members', ['context' => $context]));
    }

    public static function nameTaken(string $name): static
    {
        return self::forReason(BusinessRuleReason::NameTaken, __('stables.errors.updated.name_taken', ['name' => $name]));
    }
}
