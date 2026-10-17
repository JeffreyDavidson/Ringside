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
        $context = self::formatModelContext($entity);
        $details = $injuryDetails ? " ({$injuryDetails})" : '';

        return self::forReason(BusinessRuleReason::Injured, __('individuals.errors.reinstate.injured', ['context' => $context, 'details' => $details]));
    }

    public static function available(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return self::forReason(BusinessRuleReason::NotSuspended, __('individuals.errors.reinstate.not_suspended', ['context' => $context]));
    }
}
