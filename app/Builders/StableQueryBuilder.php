<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\Activation;
use App\Models\Retirement;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModelClass of \App\Models\Stable
 *
 * @extends Builder<\App\Models\Stable>
 */
class StableQueryBuilder extends Builder
{
    /**
     * Scope a query to only include retired models.
     */
    public function retired(): StableQueryBuilder
    {
        return $this->whereHas('currentRetirement');
    }

    /**
     * Scope a query to include current retirement date.
     */
    public function withCurrentRetiredAtDate(): StableQueryBuilder
    {
        return $this->addSelect([
            'current_retired_at' => Retirement::select('started_at')
                ->whereColumn('retiree_id', $this->qualifyColumn('id'))
                ->where('retiree_type', $this->model)
                ->latest('started_at')
                ->limit(1),
        ])->withCasts(['current_retired_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current retirement date.
     */
    public function orderByCurrentRetiredAtDate(string $direction = 'asc'): StableQueryBuilder
    {
        return $this->orderByRaw("DATE(current_retired_at) {$direction}");
    }

    /**
     * Scope a query to only include unactivated models.
     */
    public function deactivated(): StableQueryBuilder
    {
        return $this->whereDoesntHave('currentActivation')
            ->orWhereDoesntHave('previousActivations');
    }

    /**
     * Scope a query to include current deactivation date.
     */
    public function withLastDeactivationDate(): StableQueryBuilder
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
    public function orderByLastDeactivationDate(string $direction = 'asc'): StableQueryBuilder
    {
        return $this->orderByRaw("DATE(last_deactivated_at) {$direction}");
    }

    /**
     * Scope a query to only include active models.
     */
    public function active(): StableQueryBuilder
    {
        return $this->whereHas('currentActivation');
    }

    /**
     * Scope a query to only include future activated models.
     */
    public function withFutureActivation(): StableQueryBuilder
    {
        return $this->whereHas('futureActivation');
    }

    /**
     * Scope a query to only include inactive models.
     */
    public function inactive(): StableQueryBuilder
    {
        return $this->whereHas('previousActivation')
            ->whereDoesntHave('futureActivation')
            ->whereDoesntHave('currentActivation')
            ->whereDoesntHave('currentRetirement');
    }

    /**
     * Scope a query to only include inactive models.
     */
    public function unactivated(): StableQueryBuilder
    {
        return $this->whereDoesntHave('activations');
    }

    /**
     * Scope a query to include current activation date.
     */
    public function withFirstActivatedAtDate(): StableQueryBuilder
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
    public function orderByFirstActivatedAtDate(string $direction = 'asc'): StableQueryBuilder
    {
        return $this->orderByRaw("DATE(first_activated_at) {$direction}");
    }
}
