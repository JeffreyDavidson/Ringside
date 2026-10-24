<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;

final class CannotBeSuspendedException extends BaseBusinessException
{
    public static function notEmployed(TagTeam $tagTeam): static
    {
        return self::forModel('tag-team-lifecycle.errors.suspend.unemployed', $tagTeam, reason: BusinessRuleReason::Unemployed);
    }

    public static function alreadySuspended(TagTeam $tagTeam): static
    {
        return self::forModel('tag-team-lifecycle.errors.suspend.already_suspended', $tagTeam, reason: BusinessRuleReason::AlreadySuspended);
    }
}
