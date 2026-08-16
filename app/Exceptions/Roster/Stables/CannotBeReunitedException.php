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

        return new self("{$context} cannot be reunited because it is deleted. Restore the stable first.");
    }

    public static function neverActive(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} has never been active and cannot be reunited. Use establishment instead.");
    }

    public static function currentlyActive(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is currently active and doesn't need reunion.");
    }

    public static function retired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is retired and cannot be reunited. Consider unretirement instead.");
    }

    public static function insufficientFormerMembers(Stable $stable, int $availableCount, int $minimumRequired): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be reunited: only {$availableCount} former members available, but {$minimumRequired} required.");
    }

    public static function keyMembersUnavailable(Stable $stable, string $unavailableMembers): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be reunited: key former members unavailable: {$unavailableMembers}.");
    }
}
