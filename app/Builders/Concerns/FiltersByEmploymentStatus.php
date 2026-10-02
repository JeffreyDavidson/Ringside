<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

use App\Enums\Shared\EmploymentStatus;

trait FiltersByEmploymentStatus
{
    /** Relationship existence projections read by EmploymentStatusResolver. */
    public const array EMPLOYMENT_STATUS_STATE = [
        'currentRetirement as status_current_retirement_exists',
        'currentEmployment as status_current_employment_exists',
        'futureEmployment as status_future_employment_exists',
        'employments as status_employments_exists',
    ];

    abstract public function retired(): static;

    public function whereEmploymentStatus(EmploymentStatus $status): static
    {
        return match ($status) {
            EmploymentStatus::Employed => $this->employed(),
            EmploymentStatus::FutureEmployment => $this->futureEmployed(),
            EmploymentStatus::Released => $this->released(),
            EmploymentStatus::Retired => $this->retired(),
            EmploymentStatus::Unemployed => $this->unemployed(),
        };
    }

    public function withEmploymentStatusState(): static
    {
        return $this->withExists(self::EMPLOYMENT_STATUS_STATE);
    }

    public function unemployed(): static
    {
        return $this->whereDoesntHave('currentEmployment')
            ->whereDoesntHave('previousEmployments')
            ->whereDoesntHave('futureEmployment');
    }

    public function employed(): static
    {
        return $this->whereHas('currentEmployment');
    }

    public function released(): static
    {
        return $this->whereHas('previousEmployments')
            ->whereDoesntHave('currentEmployment')
            ->whereDoesntHave('futureEmployment')
            ->whereDoesntHave('currentRetirement');
    }

    public function futureEmployed(): static
    {
        return $this->whereHas('futureEmployment');
    }
}
