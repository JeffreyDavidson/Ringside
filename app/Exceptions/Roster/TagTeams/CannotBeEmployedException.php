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
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::AlreadyEmployed, __('tag-team-lifecycle.errors.employ.already_employed', ['context' => $context]));
    }

    public static function retired(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::Retired, __('tag-team-lifecycle.errors.employ.retired', ['context' => $context]));
    }

    public static function hasFutureEmployment(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self(__('tag-team-lifecycle.errors.employ.future_employment', ['context' => $context]));
    }

    public static function partnersUnavailable(TagTeam $tagTeam, string $unavailablePartners): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self(__('tag-team-lifecycle.errors.employ.partners_unavailable', ['context' => $context, 'partners' => $unavailablePartners]));
    }
}
