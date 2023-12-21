<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

use App\Models\Suspension;

trait HasSuspensions
{
    /**
     * Scope a query to include suspended models.
     */
    public function suspended(): static
    {
        $this->whereHas('currentSuspension');

        return $this;
    }

    /**
     * Scope a query to include the mode's current suspension date.
     */
    public function withCurrentSuspendedAtDate(): static
    {
        $this->addSelect([
            'current_suspended_at' => Suspension::query()->select('started_at')
                ->whereColumn('suspendable_id', $this->qualifyColumn('id'))
                ->where('suspendable_type', $this->getModel())
                ->latest('started_at')
                ->limit(1),
        ])->withCasts(['current_suspended_at' => 'datetime']);

        return $this;
    }

    /**
     * Scope a query to order by the model's current suspension date.
     */
    public function orderByCurrentSuspendedAtDate(string $direction = 'asc'): static
    {
        $this->orderByRaw("DATE(current_suspended_at) {$direction}");

        return $this;
    }
}
