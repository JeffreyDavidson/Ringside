<?php

namespace App\Builders\Concerns;

use App\Models\Activation;

trait HasActivations
{
    /**
     * Scope a query to only include unactivated models.
     */
    public function deactivated(): self
    {
        return $this->whereDoesntHave('currentActivation')
            ->orWhereDoesntHave('previousActivations');
    }

    /**
     * Scope a query to include current deactivation date.
     */
    public function withLastDeactivationDate(): self
    {
        return $this->addSelect([
            'last_deactivated_at' => Activation::select('ended_at')
                ->whereColumn('activatable_id', $this->qualifyColumn('id'))
                ->where('activatable_type', $this->model)
                ->latest('ended_at')
                ->limit(1),
        ])->withCasts(['last_deactivated_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the models current deactivation date.
     */
    public function orderByLastDeactivationDate(string $direction = 'asc'): self
    {
        return $this->orderByRaw("DATE(last_deactivated_at) {$direction}");
    }

    /**
     * Scope a query to only include active models.
     */
    public function active(): self
    {
        return $this->whereHas('currentActivation');
    }

    /**
     * Scope a query to only include future activated models.
     */
    public function withFutureActivation(): self
    {
        return $this->whereHas('futureActivation');
    }

    /**
     * Scope a query to only include inactive models.
     */
    public function inactive(): self
    {
        return $this->whereHas('previousActivation')
            ->whereDoesntHave('futureActivation')
            ->whereDoesntHave('currentActivation')
            ->whereDoesntHave('currentRetirement');
    }

    /**
     * Scope a query to only include inactive models.
     */
    public function unactivated(): self
    {
        return $this->whereDoesntHave('activations');
    }

    /**
     * Scope a query to include current activation date.
     */
    public function withFirstActivatedAtDate(): self
    {
        return $this->addSelect([
            'first_activated_at' => Activation::select('started_at')
                ->whereColumn('activatable_id', $this->qualifyColumn('id'))
                ->where('activatable_type', $this->model)
                ->oldest('started_at')
                ->limit(1),
        ])->withCasts(['first_activated_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the models first activation date.
     */
    public function orderByFirstActivatedAtDate(string $direction = 'asc'): self
    {
        return $this->orderByRaw("DATE(first_activated_at) {$direction}");
    }
}
