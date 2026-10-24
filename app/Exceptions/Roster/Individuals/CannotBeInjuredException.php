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
        return self::forModel('individuals.errors.injure.unemployed', $entity, reason: BusinessRuleReason::Unemployed);
    }

    public static function hasFutureEmployment(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.injure.future_employment', $entity);
    }

    public static function injured(Wrestler|Manager|Referee $entity, ?string $currentInjury = null): static
    {
        $details = $currentInjury ? " ({$currentInjury})" : '';

        return self::forModel('individuals.errors.injure.already_injured', $entity, ['details' => $details], reason: BusinessRuleReason::AlreadyInjured);
    }

    public static function suspended(Wrestler|Manager|Referee $entity, ?string $suspensionReason = null): static
    {
        $details = $suspensionReason ? " ({$suspensionReason})" : '';

        return self::forModel('individuals.errors.injure.suspended', $entity, ['details' => $details], reason: BusinessRuleReason::Suspended);
    }
}
