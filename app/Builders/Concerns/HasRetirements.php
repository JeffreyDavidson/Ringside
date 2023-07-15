<?php

namespace App\Builders\Concerns;

use App\Models\Retirement;

trait HasRetirements
{
    /**
     * Scope a query to only include retired models.
     */
    public function retired(): self
    {
        return $this->whereHas('currentRetirement');
    }

    /**
     * Scope a query to include current retirement date.
     */
    public function withCurrentRetiredAtDate(): self
    {
        return $this->addSelect([
            'current_retired_at' => Retirement::query()->select('started_at')
                ->whereColumn('retiree_id', $this->getModel()->getTable().'.id')
                ->where('retiree_type', $this->getModel())
                ->latest('started_at')
                ->limit(1),
        ])->withCasts(['current_retired_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current retirement date.
     */
    public function orderByCurrentRetiredAtDate(string $direction = 'asc'): self
    {
        return $this->orderByRaw("DATE(current_retired_at) {$direction}");
    }
}
