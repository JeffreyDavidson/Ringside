<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Individuals;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\Wrestlers\Wrestler;

final class CannotBeDeletedException extends BaseBusinessException
{
    public static function alreadyDeleted(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} cannot be deleted because it is already deleted.");
    }

    public static function bookedInUpcomingMatch(Wrestler|Referee $individual): static
    {
        $context = self::formatModelContext($individual);

        return self::forReason(
            BusinessRuleReason::BookedInMatch,
            __('core.delete_rejections.booked_in_match', ['context' => $context]),
        );
    }
}
