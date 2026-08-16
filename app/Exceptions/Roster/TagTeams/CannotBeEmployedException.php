<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\TagTeams\TagTeam;

final class CannotBeEmployedException extends BaseBusinessException
{
    public static function alreadyEmployed(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::AlreadyEmployed, "{$context} is already employed.");
    }

    public static function retired(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return self::forReason(BusinessRuleReason::Retired, "{$context} is retired and cannot be employed. Consider unretirement first.");
    }

    public static function hasFutureEmployment(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} already has future employment scheduled and cannot be employed again.");
    }

    public static function partnersUnavailable(TagTeam $tagTeam, string $unavailablePartners): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be employed due to unavailable partners: {$unavailablePartners}.");
    }
}
