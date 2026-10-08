<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;

final class CannotBeDeletedException extends BaseBusinessException
{
    public static function alreadyDeleted(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self(__('core.delete_rejections.already_deleted', ['context' => $context]));
    }

    public static function stillRetired(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self(__('core.delete_rejections.tag_team_retired', ['context' => $context]));
    }

    public static function stillEmployed(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self(__('core.delete_rejections.tag_team_employed', ['context' => $context]));
    }

    public static function bookedInUpcomingMatch(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return self::forReason(
            BusinessRuleReason::BookedInMatch,
            __('core.delete_rejections.booked_in_match', ['context' => $context]),
        );
    }
}
