<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\EmploymentStatus;
use App\Models\Manager;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Manager
 *
 * @extends Builder<TModel>
 */
class ManagerBuilder extends Builder
{
    /**
     * Scope a query to include available managers.
     */
    public function available(): static
    {
        $this->where('status', EmploymentStatus::Available);

        return $this;
    }

    /**
     * Scope a query to include released managers.
     */
    public function released(): static
    {
        $this->where('status', EmploymentStatus::Released);

        return $this;
    }

    /**
     * Scope a query to include injured managers.
     */
    public function injured(): static
    {
        $this->where('status', EmploymentStatus::Injured);

        return $this;
    }

    /**
     * Scope a query to include injured managers.
     */
    public function unemployed(): static
    {
        $this->where('status', EmploymentStatus::Unemployed);

        return $this;
    }

    /**
     * Scope a query to include retired managers.
     */
    public function retired(): static
    {
        $this->where('status', EmploymentStatus::Retired);

        return $this;
    }

    /**
     * Scope a query to include suspended managers.
     */
    public function suspended(): static
    {
        $this->where('status', EmploymentStatus::Suspended);

        return $this;
    }

    /**
     * Scope a query to include futre employed managers.
     */
    public function futureEmployed(): static
    {
        $this->where('status', EmploymentStatus::FutureEmployment);

        return $this;
    }
}
