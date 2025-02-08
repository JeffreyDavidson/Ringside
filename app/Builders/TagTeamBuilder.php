<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\EmploymentStatus;
use App\Models\TagTeam;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of TagTeam
 *
 * @extends Builder<TModel>
 */
class TagTeamBuilder extends Builder
{
    /**
     * Scope a query to include bookable tag teams.
     */
    public function bookable(): static
    {
        $this->where('status', EmploymentStatus::Bookable);

        return $this;
    }

    /**
     * Scope a query to include unbookable tag teams.
     */
    public function unbookable(): static
    {
        $this->where('status', EmploymentStatus::Unbookable);

        return $this;
    }

    /**
     * Scope a query to include retired tag teams.
     */
    public function retired(): static
    {
        $this->where('status', EmploymentStatus::Retired);

        return $this;
    }

    /**
     * Scope a query to include unbookable tag teams.
     */
    public function unemployed(): static
    {
        $this->where('status', EmploymentStatus::Unemployed);

        return $this;
    }

    /**
     * Scope a query to include suspended tag teams.
     */
    public function suspended(): static
    {
        $this->where('status', EmploymentStatus::Suspended);

        return $this;
    }

    /**
     * Scope a query to include released tag teams.
     */
    public function released(): static
    {
        $this->where('status', EmploymentStatus::Released);

        return $this;
    }

    /**
     * Scope a query to include future employed tag teams.
     */
    public function futureEmployed(): static
    {
        $this->where('status', EmploymentStatus::FutureEmployment);

        return $this;
    }
}
