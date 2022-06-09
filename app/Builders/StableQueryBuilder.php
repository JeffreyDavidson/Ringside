<?php

declare(strict_types=1);

namespace App\Builders;

use App\Models\Activation;
use App\Models\Retirement;
use Illuminate\Database\Eloquent\Builder;

class StableQueryBuilder extends Builder
{
    /**
     * Scope a query to only include retired models.
     *
     * @return \App\Builders\StableQueryBuilder
     */
    public function retired()
    {
        return $this->whereHas('currentRetirement');
    }

    /**
     * Scope a query to include current retirement date.
     *
     * @return \App\Builders\StableQueryBuilder
     */
    public function withCurrentRetiredAtDate()
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
     *
     * @param  string  $direction
     * @return \App\Builders\StableQueryBuilder
     */
    public function orderByCurrentRetiredAtDate($direction = 'asc')
    {
        return $this->orderByRaw("DATE(current_retired_at) {$direction}");
    }

    /**
     * Scope a query to only include unactivated models.
     *
     * @return \App\Builders\StableQueryBuilder
     */
    public function deactivated()
    {
        return $this->whereDoesntHave('currentActivation')
            ->orWhereDoesntHave('previousActivations');
    }

    /**
     * Scope a query to include current deactivation date.
     *
     * @return \App\Builders\StableQueryBuilder
     */
    public function withLastDeactivationDate()
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
     *
     * @param  string  $direction
     * @return \App\Builders\StableQueryBuilder
     */
    public function orderByLastDeactivationDate(string $direction = 'asc')
    {
        return $this->orderByRaw("DATE(last_deactivated_at) {$direction}");
    }

    /**
     * Scope a query to only include active models.
     *
     * @return \App\Builders\StableQueryBuilder
     */
    public function active()
    {
        return $this->whereHas('currentActivation');
    }

    /**
     * Scope a query to only include future activated models.
     *
     * @return \App\Builders\StableQueryBuilder
     */
    public function withFutureActivation()
    {
        return $this->whereHas('futureActivation');
    }

    /**
     * Scope a query to only include inactive models.
     *
     * @return \App\Builders\StableQueryBuilder
     */
    public function inactive()
    {
        return $this->whereHas('previousActivation')
            ->whereDoesntHave('futureActivation')
            ->whereDoesntHave('currentActivation')
            ->whereDoesntHave('currentRetirement');
    }

    /**
     * Scope a query to only include inactive models.
     *
     * @return \App\Builders\StableQueryBuilder
     */
    public function unactivated()
    {
        return $this->whereDoesntHave('activations');
    }

    /**
     * Scope a query to include current activation date.
     *
     * @return \App\Builders\StableQueryBuilder
     */
    public function withFirstActivatedAtDate()
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
     *
     * @param  string  $direction
     * @return \App\Builders\StableQueryBuilder
     */
    public function orderByFirstActivatedAtDate(string $direction = 'asc')
    {
        return $this->orderByRaw("DATE(first_activated_at) {$direction}");
    }
}
