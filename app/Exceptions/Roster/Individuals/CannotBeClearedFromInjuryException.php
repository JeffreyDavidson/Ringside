<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Individuals;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class CannotBeClearedFromInjuryException extends BaseBusinessException
{
    public static function notInjured(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.clear_from_injury.not_injured', $entity, reason: BusinessRuleReason::NotInjured);
    }
}
