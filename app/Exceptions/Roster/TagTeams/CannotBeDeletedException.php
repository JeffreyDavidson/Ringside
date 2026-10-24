<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;

final class CannotBeDeletedException extends BaseBusinessException
{
    public static function alreadyDeleted(TagTeam $tagTeam): static
    {
        return self::forModel('core.delete_rejections.already_deleted', $tagTeam);
    }

    public static function stillRetired(TagTeam $tagTeam): static
    {
        return self::forModel('core.delete_rejections.tag_team_retired', $tagTeam);
    }

    public static function stillEmployed(TagTeam $tagTeam): static
    {
        return self::forModel('core.delete_rejections.tag_team_employed', $tagTeam);
    }

    public static function bookedInUpcomingMatch(TagTeam $tagTeam): static
    {
        return self::forModel('core.delete_rejections.booked_in_match', $tagTeam, reason: BusinessRuleReason::BookedInMatch);
    }
}
