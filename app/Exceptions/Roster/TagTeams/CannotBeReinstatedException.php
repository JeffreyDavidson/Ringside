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
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::NotSuspended, __('tag-team-lifecycle.errors.reinstate.not_suspended', ['context' => $context]));
    }
}
