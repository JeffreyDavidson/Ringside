<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Individuals;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Wrestlers\Wrestler;

final class CannotBeInjuredException extends BaseBusinessException
{
    public static function unemployed(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return self::forReason(BusinessRuleReason::Unemployed, "{$context} is unemployed and cannot be injured.");
    }

    public static function retired(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} is retired and cannot be injured.");
    }

    public static function hasFutureEmployment(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} has not been officially employed and cannot be injured.");
    }

    public static function injured(Wrestler|Manager|Referee $entity, ?string $currentInjury = null): static
    {
        $context = self::formatModelContext($entity);
        $injury = $currentInjury ? " ({$currentInjury})" : '';

        return self::forReason(BusinessRuleReason::AlreadyInjured, "{$context} is already currently injured{$injury}.");
    }

    public static function suspended(Wrestler|Manager|Referee $entity, ?string $suspensionReason = null): static
    {
        $context = self::formatModelContext($entity);
        $reason = $suspensionReason ? " ({$suspensionReason})" : '';

        return self::forReason(BusinessRuleReason::Suspended, "{$context} is suspended{$reason} and cannot be injured.");
    }
}
