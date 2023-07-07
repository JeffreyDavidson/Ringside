<?php

namespace App\Builders\Concerns;

use App\Models\Injury;

trait HasInjuries
{
    /**
     * Scope a query to only include injured models.
     */
    public function injured(): self
    {
        return $this->whereHas('currentInjury');
    }

    /**
     * Scope a query to include current injured date.
     */
    public function withCurrentInjuredAtDate(): self
    {
        return $this->addSelect([
            'current_injured_at' => Injury::select('started_at')
                ->whereColumn('injurable_id', $this->qualifyColumn('id'))
                ->where('injurable_type', $this->getModel())
                ->latest('started_at')
                ->limit(1),
        ])->withCasts(['current_injured_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current injured date.
     */
    public function orderByCurrentInjuredAtDate(string $direction = 'asc'): self
    {
        return $this->orderByRaw("DATE(current_injured_at) $direction");
    }
}