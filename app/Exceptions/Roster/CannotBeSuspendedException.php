<?php

declare(strict_types=1);

namespace App\Exceptions\Roster;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Wrestlers\Wrestler;

final class CannotBeSuspendedException extends BaseBusinessException
{
    public static function unemployed(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return self::forReason(BusinessRuleReason::Unemployed, "{$context} is unemployed and cannot be suspended.");
    }

    public static function hasFutureEmployment(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} has not been officially employed and cannot be suspended.");
    }

    public static function retired(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} is retired and cannot be suspended.");
    }

    public static function released(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} is released and cannot be suspended.");
    }

    public static function suspended(Wrestler|Manager|Referee $entity, ?string $currentSuspensionReason = null): static
    {
        $context = self::formatModelContext($entity);
        $reason = $currentSuspensionReason ? " ({$currentSuspensionReason})" : '';

        return self::forReason(BusinessRuleReason::AlreadySuspended, "{$context} is already suspended{$reason}.");
    }

    public static function injured(Wrestler|Manager|Referee $entity, ?string $injuryDetails = null): static
    {
        $context = self::formatModelContext($entity);
        $injury = $injuryDetails ? " ({$injuryDetails})" : '';

        return self::forReason(BusinessRuleReason::Injured, "{$context} is injured{$injury} and cannot be suspended.");
    }
}
