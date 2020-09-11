<?php

namespace App\Models\Concerns;

use App\Exceptions\CannotBeRetiredException;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\Retirement;

trait CanBeRetired
{
    /**
     * Get the retirements of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function retirements()
    {
        return $this->morphMany(Retirement::class, 'retiree');
    }

    /**
     * Get the current retirement of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentRetirement()
    {
        return $this->retirements()
                    ->where('started_at', '<=', now())
                    ->whereNull('ended_at')
                    ->limit(1);
    }

    /**
     * Get the previous retirements of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousRetirements()
    {
        return $this->retirements()
                    ->whereNotNull('ended_at');
    }

    /**
     * Get the previous employment of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousRetirement()
    {
        return $this->previousRetirements()
                    ->latest('ended_at')
                    ->limit(1);
    }

    /**
     * Scope a query to only include retired models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRetired($query)
    {
        return $this->whereHas('currentRetirement');
    }

    /**
     * Determine if a model is retired.
     *
     * @return bool
     */
    public function getIsRetiredCachedAttribute()
    {
        return $this->status === 'retired';
    }

    /**
     * Scope a query to only include unemployed models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithCurrentRetiredAtDate($query)
    {
        return $query->addSelect(['current_retired_at' => Retirement::select('started_at')
            ->whereColumn('retiree_id', $this->getTable().'.id')
            ->where('retiree_type', $this->getMorphClass())
            ->oldest('started_at')
            ->limit(1),
        ])->withCasts(['current_retired_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the models current retirement date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeOrderByCurrentRetiredAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_retired_at) $direction");
    }

    /**
     * Retire a model.
     *
     * @return bool
     */
    abstract public function retire();

    /**
     * Unretire a model.
     *
     * @param  string|null $unretiredAt
     * @return bool
     */
    public function unretire($unretiredAt = null)
    {
        if ($this->canBeUnretired()) {
            $unretiredDate = $unretiredAt ?: now();

            $this->currentRetirement()->update(['ended_at' => $unretiredDate]);
            $this->employ($unretiredAt);

            return $this->touch();
        }
    }

    /**
     * Check to see if the model is retired.
     *
     * @return bool
     */
    public function isRetired()
    {
        return $this->whereHas('currentRetirement')->exists();
    }

    /**
     * Determine if the model can be retired.
     *
     * @return bool
     */
    public function canBeRetired()
    {
        if ($this->isUnemployed() || $this->isReleased() || $this->hasFutureEmployment()) {
            throw new CannotBeRetiredException('Entity cannot be retired. This entity does not have an active employment.');
        }

        if ($this->isRetired()) {
            throw new CannotBeRetiredException('Entity cannot be retired. This entity is retired.');
        }

        return true;
    }

    /**
     * Determine if the model can be retired.
     *
     * @return bool
     */
    public function canBeUnretired()
    {
        if (! $this->isRetired()) {
            throw new CannotBeUnretiredException('Entity cannot be unretired. This entity is not retired.');
        }

        return true;
    }
}
