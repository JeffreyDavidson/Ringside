<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;

final class CannotBeRetiredException extends BaseBusinessException
{
    public static function notEmployed(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::Unemployed, "{$context} is not currently employed and cannot be retired.");
    }

    public static function alreadyRetired(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::AlreadyRetired, "{$context} is already retired.");
    }
}
