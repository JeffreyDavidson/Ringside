<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\TitleStatus;
use App\Models\Activation;
use App\Models\Retirement;
use Illuminate\Database\Eloquent\Builder;

/**
 * The query builder attached to a title.
 *
 * @template TModelClass of \App\Models\Title
 *
 * @extends Builder<TModelClass>
 */
class TitleQueryBuilder extends Builder
{
    /**
     * Scope a query to only include retired models.
     */
    public function retired(): TitleQueryBuilder
    {
        return $this->whereHas('currentRetirement');
    }

    /**
     * Scope a query to include current retirement date.
     */
    public function withCurrentRetiredAtDate(): TitleQueryBuilder
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
     */
    public function orderByCurrentRetiredAtDate(string $direction = 'asc'): TitleQueryBuilder
    {
        return $this->orderByRaw("DATE(current_retired_at) {$direction}");
    }

    /**
     * Scope a query to only include unactivated models.
     */
    public function deactivated(): TitleQueryBuilder
    {
        return $this->whereDoesntHave('currentActivation')
            ->orWhereDoesntHave('previousActivations');
    }

    /**
     * Scope a query to include current deactivation date.
     */
    public function withLastDeactivationDate(): TitleQueryBuilder
    {
        return $this->addSelect([
            'last_deactivated_at' => Activation::select('ended_at')
                ->whereColumn('activatable_id', $this->getModel()->getTable().'.id')
                ->where('activatable_type', $this->getModel())
                ->latest('ended_at')
                ->limit(1),
        ])->withCasts(['last_deactivated_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the models current deactivation date.
     */
    public function orderByLastDeactivationDate(string $direction = 'asc'): TitleQueryBuilder
    {
        return $this->orderByRaw("DATE(last_deactivated_at) {$direction}");
    }

    /**
     * Scope a query to only include active models.
     */
    public function active(): TitleQueryBuilder
    {
        return $this->whereHas('currentActivation');
    }

    /**
     * Scope a query to only include future activated models.
     */
    public function withFutureActivation(): TitleQueryBuilder
    {
        return $this->whereHas('futureActivation');
    }

    /**
     * Scope a query to only include inactive models.
     */
    public function inactive(): TitleQueryBuilder
    {
        return $this->whereHas('previousActivation')
            ->whereDoesntHave('futureActivation')
            ->whereDoesntHave('currentActivation')
            ->whereDoesntHave('currentRetirement');
    }

    /**
     * Scope a query to only include inactive models.
     */
    public function unactivated(): TitleQueryBuilder
    {
        return $this->whereDoesntHave('activations');
    }

    /**
     * Scope a query to include current activation date.
     */
    public function withFirstActivatedAtDate(): TitleQueryBuilder
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
     */
    public function orderByFirstActivatedAtDate(string $direction = 'asc'): TitleQueryBuilder
    {
        return $this->orderByRaw("DATE(first_activated_at) {$direction}");
    }

    /**
     * Scope a query to only include competable models.
     */
    public function competable(): TitleQueryBuilder
    {
        return $this->where('status', TitleStatus::ACTIVE);
    }
}
