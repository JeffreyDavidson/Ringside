<?php

namespace App\Builders\Concerns;

use App\Models\Injury;

trait HasInjuries
{
    /**
     * Scope a query to include injured models.
     */
    public function injured(): self
    {
        $this->whereHas('currentInjury');

        return $this;
    }

    /**
     * Scope a query to include the mode's current injury date.
     */
    public function withCurrentInjuredAtDate(): self
    {
        $this->addSelect([
            'current_injured_at' => Injury::query()->select('started_at')
                ->whereColumn('injurable_id', $this->qualifyColumn('id'))
                ->where('injurable_type', $this->getModel())
                ->latest('started_at')
                ->limit(1),
        ])->withCasts(['current_injured_at' => 'datetime']);

        return $this;
    }

    /**
     * Scope a query to order by the model's current injury date.
     */
    public function orderByCurrentInjuredAtDate(string $direction = 'asc'): self
    {
        $this->orderByRaw("DATE(current_injured_at) {$direction}");

        return $this;
    }
}
