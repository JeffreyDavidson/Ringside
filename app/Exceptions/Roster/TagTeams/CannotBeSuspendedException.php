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
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::Unemployed, __('tag-team-lifecycle.errors.suspend.unemployed', ['context' => $context]));
    }

    public static function alreadySuspended(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::AlreadySuspended, __('tag-team-lifecycle.errors.suspend.already_suspended', ['context' => $context]));
    }
}
