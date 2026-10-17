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
        $context = self::formatModelContext($entity);

        return self::forReason(BusinessRuleReason::AlreadyEmployed, __('individuals.errors.employ.already_employed', ['context' => $context]));
    }

    public static function retired(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return self::forReason(BusinessRuleReason::Retired, __('individuals.errors.employ.retired', ['context' => $context]));
    }

    public static function hasFutureEmployment(Wrestler|Manager|Referee $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self(__('individuals.errors.employ.future_employment', ['context' => $context]));
    }
}
