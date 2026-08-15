<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Shared\EmploymentStatus;
use App\Lifecycle\EmploymentStatusResolver;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasComputedEmploymentStatus
{
    /** @return Attribute<EmploymentStatus, never> */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): EmploymentStatus => EmploymentStatusResolver::resolve(
            isRetired: array_key_exists('status_current_retirement_exists', $this->attributes)
                ? (bool) $this->attributes['status_current_retirement_exists']
                : $this->isRetired(),
            isEmployed: array_key_exists('status_current_employment_exists', $this->attributes)
                ? (bool) $this->attributes['status_current_employment_exists']
                : $this->isEmployed(),
            hasFutureEmployment: array_key_exists('status_future_employment_exists', $this->attributes)
                ? (bool) $this->attributes['status_future_employment_exists']
                : $this->hasFutureEmployment(),
            hasEmploymentHistory: array_key_exists('status_employments_exists', $this->attributes)
                ? (bool) $this->attributes['status_employments_exists']
                : $this->hasEmploymentHistory(),
        ));
    }
}
