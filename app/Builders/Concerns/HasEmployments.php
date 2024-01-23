<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

use App\Models\Employment;

trait HasEmployments
{
    /**
     * Scope a query to include released models.
     */
    public function released(): static
    {
        $this->whereHas('previousEmployment')
            ->whereDoesntHave('currentEmployment')
            ->whereDoesntHave('currentRetirement');

        return $this;
    }

    /**
     * Scope a query to include the model's release date.
     */
    public function withReleasedAtDate(): static
    {
        $this->addSelect([
            'released_at' => Employment::query()->select('ended_at')
                ->whereColumn('employable_id', $this->getModel()->getTable().'.id')
                ->where('employable_type', $this->getModel())
                ->latest('ended_at')
                ->limit(1),
        ])->withCasts(['released_at' => 'datetime']);

        return $this;
    }

    /**
     * Scope a query to order by the model's current released date.
     */
    public function orderByCurrentReleasedAtDate(string $direction = 'asc'): static
    {
        $this->orderByRaw("DATE(current_released_at) {$direction}");

        return $this;
    }

    /**
     * Scope a query to include employed models.
     */
    public function employed(): static
    {
        $this->whereHas('currentEmployment');

        return $this;
    }

    /**
     * Scope a query to include model's that have future employment.
     */
    public function futureEmployed(): static
    {
        $this->whereHas('futureEmployment');

        return $this;
    }

    /**
     * Scope a query to include unemployed models.
     */
    public function unemployed(): static
    {
        $this->whereDoesntHave('employments');

        return $this;
    }

    /**
     * Scope a query to include the model's first employment date.
     */
    public function withFirstEmployedAtDate(): static
    {
        $this->addSelect([
            'first_employed_at' => Employment::query()->select('started_at')
                ->whereColumn('employable_id', $this->qualifyColumn('id'))
                ->where('employable_type', $this->getModel())
                ->oldest('started_at')
                ->limit(1),
        ])->withCasts(['first_employed_at' => 'datetime']);

        return $this;
    }

    /**
     * Scope a query to order by the model's first employment date.
     */
    public function orderByFirstEmployedAtDate(string $direction = 'asc'): static
    {
        $this->orderByRaw("DATE(first_employed_at) {$direction}");

        return $this;
    }
}
