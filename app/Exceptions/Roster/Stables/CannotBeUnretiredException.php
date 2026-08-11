<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Stables\Stable;

final class CannotBeUnretiredException extends BaseBusinessException
{
    public static function deleted(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be unretired because it is deleted. Restore the stable first.");
    }

    public static function notRetired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is not retired and cannot be unretired.");
    }

    public static function nameConflict(Stable $stable, string $conflictingStableName): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be unretired: name conflicts with existing stable '{$conflictingStableName}'.");
    }

    public static function noAvailableFormerMembers(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be unretired: no former members are currently available.");
    }

    public static function insufficientFormerMembers(Stable $stable, int $availableCount, int $minimumRequired): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be unretired: only {$availableCount} former members available, but {$minimumRequired} required.");
    }

    public static function keyMembersUnavailable(Stable $stable, string $unavailableMembers): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be unretired: key former members unavailable: {$unavailableMembers}.");
    }
}
