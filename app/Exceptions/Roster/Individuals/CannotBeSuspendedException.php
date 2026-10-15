<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Individuals;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class CannotBeSuspendedException extends BaseBusinessException
{
    public static function unemployed(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return self::forReason(BusinessRuleReason::Unemployed, __('individuals.errors.suspend.unemployed', ['context' => $context]));
    }

    public static function hasFutureEmployment(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self(__('individuals.errors.suspend.future_employment', ['context' => $context]));
    }

    public static function retired(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self(__('individuals.errors.suspend.retired', ['context' => $context]));
    }

    public static function released(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self(__('individuals.errors.suspend.released', ['context' => $context]));
    }

    public static function suspended(Wrestler|Manager|Referee $entity, ?string $currentSuspensionReason = null): static
    {
        $context = self::formatModelContext($entity);
        $details = $currentSuspensionReason ? " ({$currentSuspensionReason})" : '';

        return self::forReason(BusinessRuleReason::AlreadySuspended, __('individuals.errors.suspend.already_suspended', ['context' => $context, 'details' => $details]));
    }

    public static function injured(Wrestler|Manager|Referee $entity, ?string $injuryDetails = null): static
    {
        $context = self::formatModelContext($entity);
        $details = $injuryDetails ? " ({$injuryDetails})" : '';

        return self::forReason(BusinessRuleReason::Injured, __('individuals.errors.suspend.injured', ['context' => $context, 'details' => $details]));
    }
}
