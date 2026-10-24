<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;

final class CannotBeReinstatedException extends BaseBusinessException
{
    public static function notSuspended(TagTeam $tagTeam): static
    {
        return self::forModel('tag-team-lifecycle.errors.reinstate.not_suspended', $tagTeam, reason: BusinessRuleReason::NotSuspended);
    }
}
