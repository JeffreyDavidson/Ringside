<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeReunitedException extends BaseBusinessException
{
    public static function deleted(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.reunited.deleted', ['context' => $context]));
    }

    public static function neverActive(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.reunited.never_active', ['context' => $context]));
    }

    public static function currentlyActive(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.reunited.currently_active', ['context' => $context]));
    }

    public static function retired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.reunited.retired', ['context' => $context]));
    }

    public static function insufficientFormerMembers(Stable $stable, int $availableCount, int $minimumRequired): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.reunited.insufficient_former_members', ['context' => $context, 'available_count' => $availableCount, 'minimum_required' => $minimumRequired]));
    }

    /** @param array<int, string> $memberNames */
    public static function membersNotAvailable(Stable $stable, array $memberNames): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.reunited.members_not_available', ['context' => $context, 'members' => implode(', ', $memberNames)]));
    }

    public static function belowMinimum(Stable $stable, int $memberCount, int $minimumRequired): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.reunited.below_minimum', ['context' => $context, 'member_count' => $memberCount, 'minimum_required' => $minimumRequired]));
    }
}
