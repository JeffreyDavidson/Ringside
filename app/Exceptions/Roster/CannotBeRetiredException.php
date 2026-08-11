<?php

declare(strict_types=1);

namespace App\Exceptions\Roster;

use App\Enums\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Wrestlers\Wrestler;

final class CannotBeRetiredException extends BaseBusinessException
{
    public static function unemployed(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return self::forReason(BusinessRuleReason::Unemployed, "{$context} is currently unemployed and cannot be retired.");
    }

    public static function alreadyRetired(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return self::forReason(BusinessRuleReason::AlreadyRetired, "{$context} is already retired and cannot be retired again.");
    }

    public static function hasFutureEmployment(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} has future employment scheduled and cannot be retired.");
    }
}
