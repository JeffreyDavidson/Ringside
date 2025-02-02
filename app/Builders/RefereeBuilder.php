<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\RefereeStatus;
use App\Models\Referee;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Referee
 *
 * @extends Builder<TModel>
 */
class RefereeBuilder extends Builder
{
    /**
     * Scope a query to include bookable referees.
     */
    public function bookable(): static
    {
        $this->where('status', RefereeStatus::Bookable);

        return $this;
    }

    /**
     * Scope a query to include injured referees.
     */
    public function injured(): static
    {
        $this->where('status', RefereeStatus::Injured);

        return $this;
    }

    /**
     * Scope a query to include unemployed referees.
     */
    public function unemployed(): static
    {
        $this->where('status', RefereeStatus::Unemployed);

        return $this;
    }

    /**
     * Scope a query to include retired referees.
     */
    public function retired(): static
    {
        $this->where('status', RefereeStatus::Retired);

        return $this;
    }

    /**
     * Scope a query to include released referees.
     */
    public function released(): static
    {
        $this->where('status', RefereeStatus::Released);

        return $this;
    }

    /**
     * Scope a query to include suspended referees.
     */
    public function suspended(): static
    {
        $this->where('status', RefereeStatus::Suspended);

        return $this;
    }

    /**
     * Scope a query to include with future employed referees.
     */
    public function futureEmployed(): static
    {
        $this->where('status', RefereeStatus::FutureEmployment);

        return $this;
    }
}
