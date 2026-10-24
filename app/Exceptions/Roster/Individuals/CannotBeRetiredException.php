<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Individuals;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class CannotBeRetiredException extends BaseBusinessException
{
    public static function unemployed(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.retire.unemployed', $entity, reason: BusinessRuleReason::Unemployed);
    }

    public static function alreadyRetired(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.retire.already_retired', $entity, reason: BusinessRuleReason::AlreadyRetired);
    }

    public static function hasFutureEmployment(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.retire.future_employment', $entity);
    }
}
