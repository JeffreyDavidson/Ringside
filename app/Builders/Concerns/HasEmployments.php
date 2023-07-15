<?php

namespace App\Builders\Concerns;

use App\Models\Employment;

trait HasEmployments
{
    /**
     * Scope a query to include released models.
     */
    public function released(): self
    {
        return $this->whereHas('previousEmployment')
            ->whereDoesntHave('currentEmployment')
            ->whereDoesntHave('currentRetirement');
    }

    /**
     * Scope a query to include released date.
     */
    public function withReleasedAtDate(): self
    {
        return $this->addSelect([
            'released_at' => Employment::query()->select('ended_at')
                ->whereColumn('employable_id', $this->getModel()->getTable().'.id')
                ->where('employable_type', $this->getModel())
                ->latest('ended_at')
                ->limit(1),
        ])->withCasts(['released_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current released date.
     */
    public function orderByCurrentReleasedAtDate(string $direction = 'asc'): self
    {
        return $this->orderByRaw("DATE(current_released_at) {$direction}");
    }

    /**
     * Scope a query to include employed models.
     */
    public function employed(): self
    {
        return $this->whereHas('currentEmployment');
    }

    /**
     * Scope a query to only include future employed models.
     */
    public function futureEmployed(): self
    {
        return $this->whereHas('futureEmployment');
    }

    /**
     * Scope a query to include unemployed models.
     */
    public function unemployed(): self
    {
        return $this->whereDoesntHave('employments');
    }

    /**
     * Scope a query to include first employment date.
     */
    public function withFirstEmployedAtDate(): self
    {
        return $this->addSelect([
            'first_employed_at' => Employment::query()->select('started_at')
                ->whereColumn('employable_id', $this->qualifyColumn('id'))
                ->where('employable_type', $this->getModel())
                ->oldest('started_at')
                ->limit(1),
        ])->withCasts(['first_employed_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's first employment date.
     */
    public function orderByFirstEmployedAtDate(string $direction = 'asc'): self
    {
        return $this->orderByRaw("DATE(first_employed_at) {$direction}");
    }
}
