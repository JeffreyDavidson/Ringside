<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Individuals;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class CannotBeReleasedException extends BaseBusinessException
{
    public static function unemployed(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.release.unemployed', $entity, reason: BusinessRuleReason::Unemployed);
    }

    public static function hasFutureEmployment(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.release.future_employment', $entity);
    }
}
