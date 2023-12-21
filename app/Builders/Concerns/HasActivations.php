<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

use App\Models\Activation;

/**
 * @mixin \Eloquent
 */
trait HasActivations
{
    /**
     * Scope a query to include deactivated models.
     */
    public function deactivated(): static
    {
        $this->whereDoesntHave('currentActivation')
            ->orWhereDoesntHave('previousActivations');

        return $this;
    }

    /**
     * Scope a query to include model's last deactivation date.
     */
    public function withLastDeactivationDate(): static
    {
        $this->addSelect([
            'last_deactivated_at' => Activation::query()->select('ended_at')
                ->whereColumn('activatable_id', $this->qualifyColumn('id'))
                ->where('activatable_type', $this->model)
                ->latest('ended_at')
                ->limit(1),
        ])->withCasts(['last_deactivated_at' => 'datetime']);

        return $this;
    }

    /**
     * Scope a query to order by the model's last deactivation date.
     */
    public function orderByLastDeactivationDate(string $direction = 'asc'): static
    {
        $this->orderByRaw("DATE(last_deactivated_at) {$direction}");

        return $this;
    }

    /**
     * Scope a query to include active models.
     */
    public function active(): static
    {
        $this->whereHas('currentActivation');

        return $this;
    }

    /**
     * Scope a query to include models with future activation.
     */
    public function withFutureActivation(): static
    {
        $this->whereHas('futureActivation');

        return $this;
    }

    /**
     * Scope a query to include inactive models.
     */
    public function inactive(): static
    {
        $this->whereHas('previousActivation')
            ->whereDoesntHave('futureActivation')
            ->whereDoesntHave('currentActivation')
            ->whereDoesntHave('currentRetirement');

        return $this;
    }

    /**
     * Scope a query to include unactivated models.
     */
    public function unactivated(): static
    {
        $this->whereDoesntHave('activations');

        return $this;
    }

    /**
     * Scope a query to include the model's first activation date.
     */
    public function withFirstActivatedAtDate(): static
    {
        $this->addSelect([
            'first_activated_at' => Activation::query()->select('started_at')
                ->whereColumn('activatable_id', $this->qualifyColumn('id'))
                ->where('activatable_type', $this->model)
                ->oldest('started_at')
                ->limit(1),
        ])->withCasts(['first_activated_at' => 'datetime']);

        return $this;
    }

    /**
     * Scope a query to order by the model's first activation date.
     */
    public function orderByFirstActivatedAtDate(string $direction = 'asc'): static
    {
        $this->orderByRaw("DATE(first_activated_at) {$direction}");

        return $this;
    }
}
