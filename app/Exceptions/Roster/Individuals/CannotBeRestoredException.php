<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Individuals;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class CannotBeRestoredException extends BaseBusinessException
{
    public static function notDeleted(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('individuals.errors.restore.not_deleted', $entity, reason: BusinessRuleReason::NotDeleted);
    }
}
