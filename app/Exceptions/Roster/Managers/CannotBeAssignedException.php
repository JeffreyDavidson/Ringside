<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Managers;

use App\Enums\Shared\BusinessRuleReason;
use App\Exceptions\BaseBusinessException;
use App\Models\Roster\Managers\Manager;

final class CannotBeAssignedException extends BaseBusinessException
{
    public static function deleted(Manager $manager): static
    {
        return self::forReason(BusinessRuleReason::General, __('managers.validation.not_assignable.deleted', [
            'name' => self::nameOfManager($manager),
        ]));
    }

    public static function unemployed(Manager $manager): static
    {
        return self::forReason(BusinessRuleReason::Unemployed, __('managers.validation.not_assignable.unemployed', [
            'name' => self::nameOfManager($manager),
        ]));
    }

    public static function retired(Manager $manager): static
    {
        return self::forReason(BusinessRuleReason::Retired, __('managers.validation.not_assignable.retired', [
            'name' => self::nameOfManager($manager),
        ]));
    }

    public static function suspended(Manager $manager): static
    {
        return self::forReason(BusinessRuleReason::Suspended, __('managers.validation.not_assignable.suspended', [
            'name' => self::nameOfManager($manager),
        ]));
    }

    public static function injured(Manager $manager): static
    {
        return self::forReason(BusinessRuleReason::Injured, __('managers.validation.not_assignable.injured', [
            'name' => self::nameOfManager($manager),
        ]));
    }

    private static function nameOfManager(Manager $manager): string
    {
        return "{$manager->first_name} {$manager->last_name}";
    }
}
