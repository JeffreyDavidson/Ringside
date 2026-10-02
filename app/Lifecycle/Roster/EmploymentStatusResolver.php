<?php

declare(strict_types=1);

namespace App\Lifecycle\Roster;

use App\Enums\Shared\EmploymentStatus;
use App\Models\Contracts\Employable;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Model;
use LogicException;

final class EmploymentStatusResolver
{
    /**
     * Resolve employment status from an employable model's persisted lifecycle state.
     *
     * Query projections produced by `withEmploymentStatusState` are reused when
     * available so rendering status does not introduce additional relationship queries.
     */
    public static function resolveFor(Model $model): EmploymentStatus
    {
        if (! $model instanceof Employable || ! $model instanceof Retirable) {
            throw new LogicException('Employment status requires an employable, retirable model.');
        }

        return self::resolve(
            isRetired: $model->hasCurrentRetirement(),
            isEmployed: $model->hasCurrentEmployment(),
            hasFutureEmployment: $model->hasFutureEmployment(),
            hasEmploymentHistory: $model->hasEmploymentHistory(),
        );
    }

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
