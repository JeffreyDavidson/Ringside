<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Shared\EmploymentStatus;
use App\Models\Lifecycle\Employment;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasComputedEmploymentStatus
{
    /** @return Attribute<EmploymentStatus, never> */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): EmploymentStatus => $this->computedEmploymentStatus());
    }

    protected function computedEmploymentStatus(): EmploymentStatus
    {
        if ($this->isRetired()) {
            return EmploymentStatus::Retired;
        }

        if ($this->currentEmployment instanceof Employment) {
            return EmploymentStatus::Employed;
        }

        if ($this->futureEmployment instanceof Employment) {
            return EmploymentStatus::FutureEmployment;
        }

        if ($this->previousEmployments()->exists()) {
            return EmploymentStatus::Released;
        }

        return EmploymentStatus::Unemployed;
    }
}
