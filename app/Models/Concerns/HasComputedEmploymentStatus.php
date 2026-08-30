<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Shared\EmploymentStatus;
use App\Lifecycle\Roster\EmploymentStatusResolver;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasComputedEmploymentStatus
{
    /** @return Attribute<EmploymentStatus, never> */
    protected function status(): Attribute
    {
        return Attribute::get(fn (): EmploymentStatus => EmploymentStatusResolver::resolveFor($this));
    }
}
