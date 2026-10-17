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
        $context = self::formatModelContext($entity);

        return self::forReason(BusinessRuleReason::Unemployed, __('individuals.errors.retire.unemployed', ['context' => $context]));
    }

    public static function alreadyRetired(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return self::forReason(BusinessRuleReason::AlreadyRetired, __('individuals.errors.retire.already_retired', ['context' => $context]));
    }

    public static function hasFutureEmployment(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self(__('individuals.errors.retire.future_employment', ['context' => $context]));
    }
}
