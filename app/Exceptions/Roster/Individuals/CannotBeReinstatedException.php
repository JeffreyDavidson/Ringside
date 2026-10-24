<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Individuals;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class CannotBeReinstatedException extends BaseBusinessException
{
    public static function injured(Wrestler|Manager|Referee $entity, ?string $injuryDetails = null): static
    {
        $details = $injuryDetails ? " ({$injuryDetails})" : '';

        return self::forModel('individuals.errors.reinstate.injured', $entity, ['details' => $details], reason: BusinessRuleReason::Injured);
    }

    public static function available(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.reinstate.not_suspended', $entity, reason: BusinessRuleReason::NotSuspended);
    }
}
