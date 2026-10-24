<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Individuals;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class CannotBeDeletedException extends BaseBusinessException
{
    public static function alreadyDeleted(Wrestler|Manager|Referee $entity): static
    {
        return self::forModel('core.delete_rejections.already_deleted', $entity);
    }

    public static function bookedInUpcomingMatch(Wrestler|Referee $individual): static
    {
        return self::forModel('core.delete_rejections.booked_in_match', $individual, reason: BusinessRuleReason::BookedInMatch);
    }
}
