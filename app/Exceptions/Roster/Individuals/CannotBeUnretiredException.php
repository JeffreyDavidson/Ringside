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
        $context = self::formatModelContext($entity);

        return new self(__('individuals.errors.unretire.deleted', ['context' => $context]));
    }

    public static function notRetired(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return self::forReason(BusinessRuleReason::NotRetired, __('individuals.errors.unretire.not_retired', ['context' => $context]));
    }
}
