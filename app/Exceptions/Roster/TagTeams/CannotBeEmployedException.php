<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;

final class CannotBeEmployedException extends BaseBusinessException
{
    public static function alreadyEmployed(TagTeam $tagTeam): static
    {
        return self::forModel('tag-team-lifecycle.errors.employ.already_employed', $tagTeam, reason: BusinessRuleReason::AlreadyEmployed);
    }

    public static function retired(TagTeam $tagTeam): static
    {
        return self::forModel('tag-team-lifecycle.errors.employ.retired', $tagTeam, reason: BusinessRuleReason::Retired);
    }

    public static function hasFutureEmployment(TagTeam $tagTeam): static
    {
        return self::forModel('tag-team-lifecycle.errors.employ.future_employment', $tagTeam);
    }

    public static function partnersUnavailable(TagTeam $tagTeam, string $unavailablePartners): static
    {
        return self::forModel('tag-team-lifecycle.errors.employ.partners_unavailable', $tagTeam, ['partners' => $unavailablePartners]);
    }
}
