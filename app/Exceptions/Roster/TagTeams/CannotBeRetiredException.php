<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;

final class CannotBeRetiredException extends BaseBusinessException
{
    public static function notEmployed(TagTeam $tagTeam): static
    {
        return self::forModel('tag-team-lifecycle.errors.retire.unemployed', $tagTeam, reason: BusinessRuleReason::Unemployed);
    }

    public static function alreadyRetired(TagTeam $tagTeam): static
    {
        return self::forModel('tag-team-lifecycle.errors.retire.already_retired', $tagTeam, reason: BusinessRuleReason::AlreadyRetired);
    }
}
