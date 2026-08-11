<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\TagTeams\TagTeam;

final class CannotBeSuspendedException extends BaseBusinessException
{
    public static function notEmployed(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::Unemployed, "{$context} cannot be suspended because it is not currently employed. Only employed tag teams can be suspended from active competition.");
    }

    public static function alreadySuspended(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::AlreadySuspended, "{$context} cannot be suspended because it is already suspended. Review current suspension status or consider reinstatement before applying new disciplinary measures.");
    }
}
