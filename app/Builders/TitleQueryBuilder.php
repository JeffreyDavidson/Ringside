<?php

namespace App\Builders;

use App\Enums\TitleStatus;
use App\Models\Activation;
use App\Models\Retirement;
use Illuminate\Database\Eloquent\Builder;

/**
 * The query builder attached to a title.
 *
 * @template TModelClass of \App\Models\Title
 * @extends Builder<TModelClass>
 */
class TitleQueryBuilder extends Builder
{
    /**
     * Scope a query to only include retired models.
     *
     * @return \App\Builders\TitleQueryBuilder
     */
    public function retired()
    {
        return $this->whereHas('currentRetirement');
    }

    /**
     * Scope a query to include current retirement date.
     *
     * @return \App\Builders\TitleQueryBuilder
     */
    public function withCurrentRetiredAtDate()
    {
        return $this->addSelect([
            'current_retired_at' => Retirement::select('started_at')
                ->whereColumn('retiree_id', $this->getModel()->getTable().'.id')
                ->where('retiree_type', $this->getModel())
                ->latest('started_at')
                ->limit(1),
        ])->withCasts(['current_retired_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current retirement date.
     *
     * @param  string $direction
     *
     * @return \App\Builders\TitleQueryBuilder
     */
    public function orderByCurrentRetiredAtDate($direction = 'asc')
    {
        return $this->orderByRaw("DATE(current_retired_at) {$direction}");
    }

    /**
     * Scope a query to only include unactivated models.
     *
     * @return \App\Builders\TitleQueryBuilder
     */
    public function deactivated()
    {
        return $this->whereDoesntHave('currentActivation')
            ->orWhereDoesntHave('previousActivations');
    }

    /**
     * Scope a query to include current deactivation date.
     *
     * @return \App\Builders\TitleQueryBuilder
     */
    public function withLastDeactivationDate()
    {
        return $this->addSelect([
            'last_deactivated_at' => Activation::select('ended_at')
                ->whereColumn('activatable_id', $this->getModel()->getTable().'.id')
                ->where('activatable_type', $this->getModel())
                ->orderBy('ended_at', 'desc')
                ->limit(1),
        ])->withCasts(['last_deactivated_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the models current deactivation date.
     *
     * @param  string $direction
     *
     * @return \App\Builders\TitleQueryBuilder
     */
    public function orderByLastDeactivationDate(string $direction = 'asc')
    {
        return $this->orderByRaw("DATE(last_deactivated_at) {$direction}");
    }

    /**
     * Scope a query to only include active models.
     *
     * @return \App\Builders\TitleQueryBuilder
     */
    public function active()
    {
        return $this->whereHas('currentActivation');
    }

    /**
     * Scope a query to only include future activated models.
     *
     * @return \App\Builders\TitleQueryBuilder
     */
    public function withFutureActivation()
    {
        return $this->whereHas('futureActivation');
    }

    /**
     * Scope a query to only include inactive models.
     *
     * @return \App\Builders\TitleQueryBuilder
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
     * @return \App\Builders\TitleQueryBuilder
     */
    public function unactivated()
    {
        return $this->whereDoesntHave('activations');
    }

    /**
     * Scope a query to include current activation date.
     *
     * @return \App\Builders\TitleQueryBuilder
     */
    public function withFirstActivatedAtDate()
    {
        return $this->addSelect([
            'first_activated_at' => Activation::select('started_at')
                ->whereColumn('activatable_id', $this->getModel()->getTable().'.id')
                ->where('activatable_type', $this->getModel())
                ->oldest('started_at')
                ->limit(1),
        ])->withCasts(['first_activated_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the models first activation date.
     *
     * @param  string $direction
     *
     * @return \App\Builders\TitleQueryBuilder
     */
    public function orderByFirstActivatedAtDate(string $direction = 'asc')
    {
        return $this->orderByRaw("DATE(first_activated_at) {$direction}");
    }

    /**
     * Scope a query to only include competable models.
     *
     * @return \App\Builders\TitleQueryBuilder
     */
    public function competable()
    {
        return $this->where('status', TitleStatus::active());
    }
}
