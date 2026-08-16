<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Stables\Stable;

final class CannotBeSplitException extends BaseBusinessException
{
    public static function retired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is retired and cannot be split.");
    }

    public static function notActive(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is not currently active and cannot be split.");
    }

    public static function insufficientMembers(Stable $stable, int $currentMembers, int $minimumRequired): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} has only {$currentMembers} members but requires at least {$minimumRequired} members to split.");
    }

    public static function noMembersToMove(): static
    {
        return new self('Cannot split stable: at least one member must be moved to the new stable.');
    }

    public static function allMembersMoving(): static
    {
        return new self('Cannot split stable: at least one member must remain in the original stable.');
    }

    /** @param array<int, string> $memberNames */
    public static function membersDoNotBelongToStable(array $memberNames): static
    {
        return new self('Cannot split stable: these selected members do not belong to the original stable: '.implode(', ', $memberNames).'.');
    }

    /** @param array<int, string> $memberNames */
    public static function membersUnavailable(array $memberNames): static
    {
        return new self('Cannot split stable: these selected members are unavailable: '.implode(', ', $memberNames).'.');
    }

    public static function resultingStableBelowMinimum(string $stable, int $memberCount, int $minimumRequired): static
    {
        return new self("Cannot split stable: the {$stable} stable would have {$memberCount} members but requires at least {$minimumRequired}.");
    }
}
