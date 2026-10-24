<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;

final class CannotBeReleasedException extends BaseBusinessException
{
    public static function notEmployed(TagTeam $tagTeam): static
    {
        return self::forModel('tag-team-lifecycle.errors.release.unemployed', $tagTeam, reason: BusinessRuleReason::Unemployed);
    }
}
