<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

use App\Models\Retirement;

trait HasRetirements
{
    /**
     * Scope a query to include retired models.
     */
    public function retired(): static
    {
        $this->whereHas('currentRetirement');

        return $this;
    }

    /**
     * Scope a query to include the model's current retirement date.
     */
    public function withCurrentRetiredAtDate(): static
    {
        $this->addSelect([
            'current_retired_at' => Retirement::query()->select('started_at')
                ->whereColumn('retiree_id', $this->getModel()->getTable().'.id')
                ->where('retiree_type', $this->getModel())
                ->latest('started_at')
                ->limit(1),
        ])->withCasts(['current_retired_at' => 'datetime']);

        return $this;
    }

    /**
     * Scope a query to order by the model's current retirement date.
     */
    public function orderByCurrentRetiredAtDate(string $direction = 'asc'): static
    {
        $this->orderByRaw("DATE(current_retired_at) {$direction}");

        return $this;
    }
}
