<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeReunitedException extends BaseBusinessException
{
    public static function deleted(Stable $stable): static
    {
        return self::forModel('stables.errors.reunited.deleted', $stable);
    }

    public static function neverActive(Stable $stable): static
    {
        return self::forModel('stables.errors.reunited.never_active', $stable);
    }

    public static function currentlyActive(Stable $stable): static
    {
        return self::forModel('stables.errors.reunited.currently_active', $stable);
    }

    public static function retired(Stable $stable): static
    {
        return self::forModel('stables.errors.reunited.retired', $stable);
    }

    public static function insufficientFormerMembers(Stable $stable, int $availableCount, int $minimumRequired): static
    {
        return self::forModel('stables.errors.reunited.insufficient_former_members', $stable, ['available_count' => $availableCount, 'minimum_required' => $minimumRequired]);
    }

    /** @param array<int, string> $memberNames */
    public static function membersNotAvailable(Stable $stable, array $memberNames): static
    {
        return self::forModel('stables.errors.reunited.members_not_available', $stable, ['members' => implode(', ', $memberNames)]);
    }

    public static function belowMinimum(Stable $stable, int $memberCount, int $minimumRequired): static
    {
        return self::forModel('stables.errors.reunited.below_minimum', $stable, ['member_count' => $memberCount, 'minimum_required' => $minimumRequired]);
    }
}
