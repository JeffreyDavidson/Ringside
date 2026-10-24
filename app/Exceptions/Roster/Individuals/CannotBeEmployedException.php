<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Individuals;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class CannotBeEmployedException extends BaseBusinessException
{
    public static function employed(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.employ.already_employed', $entity, reason: BusinessRuleReason::AlreadyEmployed);
    }

    public static function retired(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.employ.retired', $entity, reason: BusinessRuleReason::Retired);
    }

    public static function hasFutureEmployment(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.employ.future_employment', $entity);
    }
}
