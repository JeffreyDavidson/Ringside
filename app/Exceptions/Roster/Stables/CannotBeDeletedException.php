<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeDeletedException extends BaseBusinessException
{
    public static function alreadyDeleted(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be deleted because it is already deleted.");
    }

    public static function currentlyActive(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is currently active and cannot be deleted. Use disband action first.");
    }

    public static function futureEstablishmentScheduled(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} has a future establishment scheduled and cannot be deleted.");
    }

    public static function hasCurrentMembers(Stable $stable, int $memberCount): static
    {
        $context = self::formatModelContext($stable);
        $memberText = $memberCount === 1 ? 'member' : 'members';

        return new self("{$context} has {$memberCount} current {$memberText} and cannot be deleted. Remove members first or use disband action.");
    }
}
