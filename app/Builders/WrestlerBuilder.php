<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\EmploymentStatus;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<\App\Models\Wrestler>
 */
class WrestlerBuilder extends Builder
{
    public function unemployed(): static
    {
        $this->where('status', EmploymentStatus::Unemployed);

        return $this;
    }

    public function futureEmployed(): static
    {
        $this->where('status', EmploymentStatus::FutureEmployment);

        return $this;
    }

    public function employed(): static
    {
        $this->where('status', EmploymentStatus::Bookable);

        return $this;
    }

    /**
     * Scope a query to include bookable wrestlers.
     */
    public function bookable(): static
    {
        $this->where('status', EmploymentStatus::Bookable);

        return $this;
    }

    /**
     * Scope a query to include bookable wrestlers.
     */
    public function injured(): static
    {
        $this->where('status', EmploymentStatus::Injured);

        return $this;
    }

    /**
     * Scope a query to include bookable wrestlers.
     */
    public function retired(): static
    {
        $this->where('status', EmploymentStatus::Retired);

        return $this;
    }

    /**
     * Scope a query to include bookable wrestlers.
     */
    public function released(): static
    {
        $this->where('status', EmploymentStatus::Released);

        return $this;
    }

    /**
     * Scope a query to include bookable wrestlers.
     */
    public function suspended(): static
    {
        $this->where('status', EmploymentStatus::Suspended);

        return $this;
    }
}
