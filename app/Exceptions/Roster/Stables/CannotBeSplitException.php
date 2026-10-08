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

        return new self(__('stables.errors.split.retired', ['context' => $context]));
    }

    public static function notActive(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.split.not_active', ['context' => $context]));
    }

    public static function insufficientMembers(Stable $stable, int $currentMembers, int $minimumRequired): static
    {
        $context = self::formatModelContext($stable);

        return new self(__('stables.errors.split.insufficient_members', ['context' => $context, 'current_members' => $currentMembers, 'minimum_required' => $minimumRequired]));
    }

    public static function noMembersToMove(): static
    {
        return new self(__('stables.errors.split.no_members_to_move'));
    }

    public static function allMembersMoving(): static
    {
        return new self(__('stables.errors.split.all_members_moving'));
    }

    /** @param array<int, string> $memberNames */
    public static function membersDoNotBelongToStable(array $memberNames): static
    {
        return new self(__('stables.errors.split.members_do_not_belong_to_stable', ['members' => implode(', ', $memberNames)]));
    }

    /** @param array<int, string> $memberNames */
    public static function membersUnavailable(array $memberNames): static
    {
        return new self(__('stables.errors.split.members_unavailable', ['members' => implode(', ', $memberNames)]));
    }

    /** @param array<int, string> $wrestlerNames */
    public static function separatesTagTeamFromWrestlers(string $tagTeamName, array $wrestlerNames): static
    {
        $names = implode(', ', $wrestlerNames);

        return new self(__('stables.errors.split.separates_tag_team_from_wrestlers', ['tag_team_name' => $tagTeamName, 'names' => $names]));
    }

    public static function nameTaken(string $name): static
    {
        return new self(__('stables.errors.split.name_taken', ['name' => $name]));
    }

    public static function resultingStableBelowMinimum(string $stable, int $memberCount, int $minimumRequired): static
    {
        return new self(__('stables.errors.split.resulting_stable_below_minimum', ['stable' => $stable, 'member_count' => $memberCount, 'minimum_required' => $minimumRequired]));
    }
}
