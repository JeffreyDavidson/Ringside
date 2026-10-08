<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeUnretiredException extends BaseBusinessException
{
    public static function deleted(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.unretired.deleted', ['context' => $context]));
    }

    public static function notRetired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.unretired.not_retired', ['context' => $context]));
    }

    public static function noAvailableFormerMembers(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.unretired.no_available_former_members', ['context' => $context]));
    }

    public static function insufficientFormerMembers(Stable $stable, int $availableCount, int $minimumRequired): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.unretired.insufficient_former_members', ['context' => $context, 'available_count' => $availableCount, 'minimum_required' => $minimumRequired]));
    }

    public static function keyMembersUnavailable(Stable $stable, string $unavailableMembers): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.unretired.key_members_unavailable', ['context' => $context, 'unavailable_members' => $unavailableMembers]));
    }
}
