<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Individuals;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class CannotBeUnretiredException extends BaseBusinessException
{
    public static function deleted(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.unretire.deleted', $entity);
    }

    public static function notRetired(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.unretire.not_retired', $entity, reason: BusinessRuleReason::NotRetired);
    }
}
