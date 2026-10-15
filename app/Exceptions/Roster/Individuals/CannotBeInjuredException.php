<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Individuals;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class CannotBeInjuredException extends BaseBusinessException
{
    public static function unemployed(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return self::forReason(BusinessRuleReason::Unemployed, __('individuals.errors.injure.unemployed', ['context' => $context]));
    }

    public static function hasFutureEmployment(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self(__('individuals.errors.injure.future_employment', ['context' => $context]));
    }

    public static function injured(Wrestler|Manager|Referee $entity, ?string $currentInjury = null): static
    {
        $context = self::formatModelContext($entity);
        $details = $currentInjury ? " ({$currentInjury})" : '';

        return self::forReason(BusinessRuleReason::AlreadyInjured, __('individuals.errors.injure.already_injured', ['context' => $context, 'details' => $details]));
    }

    public static function suspended(Wrestler|Manager|Referee $entity, ?string $suspensionReason = null): static
    {
        $context = self::formatModelContext($entity);
        $details = $suspensionReason ? " ({$suspensionReason})" : '';

        return self::forReason(BusinessRuleReason::Suspended, __('individuals.errors.injure.suspended', ['context' => $context, 'details' => $details]));
    }
}
