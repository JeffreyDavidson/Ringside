<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\ActivationStatus;
use App\Models\Stable;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Stable
 *
 * @extends Builder<TModel>
 */
class StableBuilder extends Builder
{
    /**
     * Scope a query to include unactivated stables.
     */
    public function unactivated(): static
    {
        $this->where('status', ActivationStatus::Unactivated);

        return $this;
    }

    /**
     * Scope a query to include inactive stables.
     */
    public function active(): static
    {
        $this->where('status', ActivationStatus::Active);

        return $this;
    }

    /**
     * Scope a query to include retired stables.
     */
    public function retired(): static
    {
        $this->where('status', ActivationStatus::Retired);

        return $this;
    }

    /**
     * Scope a query to include inactive stables.
     */
    public function inactive(): static
    {
        $this->where('status', ActivationStatus::Inactive);

        return $this;
    }

    /**
     * Scope a query to include inactive stables.
     */
    public function withFutureActivation(): static
    {
        $this->where('status', ActivationStatus::FutureActivation);

        return $this;
    }
}
