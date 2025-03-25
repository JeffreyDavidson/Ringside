<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\ActivationStatus;
use App\Models\Title;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of Title
 *
 * @extends Builder<TModel>
 */
class TitleBuilder extends Builder
{
    /**
     * Scope a query to include competable titles.
     */
    public function competable(): static
    {
        $this->where('status', ActivationStatus::Active);

        return $this;
    }

    /**
     * Scope a query to include active titles.
     */
    public function active(): static
    {
        $this->where('status', ActivationStatus::Active);

        return $this;
    }

    /**
     * Scope a query to include retired wrestlers.
     */
    public function retired(): static
    {
        $this->where('status', ActivationStatus::Retired);

        return $this;
    }

    /**
     * Scope a query to include future activated titles.
     */
    public function withFutureActivation(): static
    {
        $this->where('status', ActivationStatus::FutureActivation);

        return $this;
    }

    /**
     * Scope a query to include inactive titles.
     */
    public function inactive(): static
    {
        $this->where('status', ActivationStatus::Inactive);

        return $this;
    }
}
