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
        return self::forModel('individuals.errors.suspend.unemployed', $entity, reason: BusinessRuleReason::Unemployed);
    }

    public static function hasFutureEmployment(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.suspend.future_employment', $entity);
    }

    public static function retired(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.suspend.retired', $entity);
    }

    public static function released(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.suspend.released', $entity);
    }

    public static function suspended(Wrestler|Manager|Referee $entity, ?string $currentSuspensionReason = null): static
    {
        $details = $currentSuspensionReason ? " ({$currentSuspensionReason})" : '';

        return self::forModel('individuals.errors.suspend.already_suspended', $entity, ['details' => $details], reason: BusinessRuleReason::AlreadySuspended);
    }

    public static function injured(Wrestler|Manager|Referee $entity, ?string $injuryDetails = null): static
    {
        $details = $injuryDetails ? " ({$injuryDetails})" : '';

        return self::forModel('individuals.errors.suspend.injured', $entity, ['details' => $details], reason: BusinessRuleReason::Injured);
    }
}
