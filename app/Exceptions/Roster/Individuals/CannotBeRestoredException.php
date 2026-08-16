<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Individuals;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class CannotBeRestoredException extends BaseBusinessException
{
    public static function notDeleted(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return self::forReason(BusinessRuleReason::NotDeleted, "{$context} is not deleted and cannot be restored.");
    }
}
