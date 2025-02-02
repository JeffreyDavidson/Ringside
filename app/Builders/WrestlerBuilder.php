<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\WrestlerStatus;
use App\Models\Wrestler;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Wrestler
 *
 * @extends Builder<TModel>
 */
class WrestlerBuilder extends Builder
{
    public function unemployed(): static
    {
        $this->where('status', WrestlerStatus::Unemployed);

        return $this;
    }

    public function futureEmployed(): static
    {
        $this->where('status', WrestlerStatus::FutureEmployment);

        return $this;
    }

    public function employed(): static
    {
        $this->where('status', WrestlerStatus::Bookable);

        return $this;
    }

    /**
     * Scope a query to include bookable wrestlers.
     */
    public function bookable(): static
    {
        $this->where('status', WrestlerStatus::Bookable);

        return $this;
    }

    /**
     * Scope a query to include bookable wrestlers.
     */
    public function injured(): static
    {
        $this->where('status', WrestlerStatus::Injured);

        return $this;
    }

    /**
     * Scope a query to include bookable wrestlers.
     */
    public function retired(): static
    {
        $this->where('status', WrestlerStatus::Retired);

        return $this;
    }

    /**
     * Scope a query to include bookable wrestlers.
     */
    public function released(): static
    {
        $this->where('status', WrestlerStatus::Released);

        return $this;
    }

    /**
     * Scope a query to include bookable wrestlers.
     */
    public function suspended(): static
    {
        $this->where('status', WrestlerStatus::Suspended);

        return $this;
    }
}
