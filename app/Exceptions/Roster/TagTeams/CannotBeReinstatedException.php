<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\TagTeams\TagTeam;

final class CannotBeReinstatedException extends BaseBusinessException
{
    public static function notSuspended(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::NotSuspended, "{$context} cannot be reinstated because it is not currently suspended. Only suspended tag teams can be reinstated to active competition.");
    }

    public static function notEmployed(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new static("{$context} cannot be reinstated because it is no longer employed. Tag teams must maintain employment status during suspension periods.");
    }
}
