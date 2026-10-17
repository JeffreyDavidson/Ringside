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
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::Unemployed, __('tag-team-lifecycle.errors.retire.unemployed', ['context' => $context]));
    }

    public static function alreadyRetired(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::AlreadyRetired, __('tag-team-lifecycle.errors.retire.already_retired', ['context' => $context]));
    }
}
