<?php

namespace App\Builders\Concerns;

use App\Models\Suspension;

trait HasSuspensions
{
    /**
     * Scope a query to include suspended models.
     */
    public function suspended(): self
    {
        return $this->whereHas('currentSuspension');
    }

    /**
     * Scope a query to include current suspension date.
     */
    public function withCurrentSuspendedAtDate(): self
    {
        return $this->addSelect([
            'current_suspended_at' => Suspension::query()->select('started_at')
                ->whereColumn('suspendable_id', $this->qualifyColumn('id'))
                ->where('suspendable_type', $this->getModel())
                ->latest('started_at')
                ->limit(1),
        ])->withCasts(['current_suspended_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current suspension date.
     */
    public function orderByCurrentSuspendedAtDate(string $direction = 'asc'): self
    {
        return $this->orderByRaw("DATE(current_suspended_at) {$direction}");
    }
}
