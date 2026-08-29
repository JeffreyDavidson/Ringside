<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster;

use App\Enums\Shared\EmploymentStatus;

final class EmploymentStatusResolver
{
    public static function resolve(
        bool $isRetired,
        bool $isEmployed,
        bool $hasFutureEmployment,
        bool $hasEmploymentHistory,
    ): EmploymentStatus {
        return match (true) {
            $isRetired => EmploymentStatus::Retired,
            $isEmployed => EmploymentStatus::Employed,
            $hasFutureEmployment => EmploymentStatus::FutureEmployment,
            $hasEmploymentHistory => EmploymentStatus::Released,
            default => EmploymentStatus::Unemployed,
        };
    }
}
