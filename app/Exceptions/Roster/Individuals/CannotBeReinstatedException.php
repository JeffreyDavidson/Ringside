<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Individuals;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class CannotBeReinstatedException extends BaseBusinessException
{
    public static function injured(Wrestler|Manager|Referee $entity, ?string $injuryDetails = null): static
    {
        $context = self::formatModelContext($entity);
        $injury = $injuryDetails ? " ({$injuryDetails})" : '';

        return self::forReason(BusinessRuleReason::Injured, "{$context} is injured{$injury} and cannot be reinstated until medically cleared.");
    }

    public static function available(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return self::forReason(BusinessRuleReason::NotSuspended, "{$context} is already available and does not need reinstatement.");
    }
}
