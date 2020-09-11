<?php

namespace App\Models\Concerns;

use App\Exceptions\CannotBeEmployedException;
use App\Models\Employment;

trait CanBeEmployed
{
    /**
     * Get all of the employments of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function employments()
    {
        return $this->morphMany(Employment::class, 'employable');
    }

    /**
     * Get the current employment of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentEmployment()
    {
        \Event::listen('Illuminate\Database\Events\QueryExecuted', function ($query) {
            //            dump($query->sql, $query->bindings, $query->time);
            \Illuminate\Support\Facades\Log::info(\Illuminate\Support\Str::replaceArray('?', $query->bindings,
                                                                                        $query->sql));
        });

        return $this->employments()
                    ->where('started_at', '<=', now())
                    ->whereNull('ended_at')
                    ->limit(1);
    }

    /**
     * Get the future employment of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function futureEmployment()
    {
        return $this->employments()
                    ->where('started_at', '>', now())
                    ->whereNull('ended_at')
                    ->limit(1);
    }

    /**
     * Get the previous employments of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousEmployments()
    {
        return $this->employments()
                    ->whereNotNull('ended_at');
    }

    /**
     * Get the previous employment of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousEmployment()
    {
        return $this->previousEmployments()
                    ->latest('ended_at')
                    ->limit(1);
    }

    /**
     * Scope a query to only include future employment models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeFutureEmployment($query)
    {
        return $query->whereHas('futureEmployment');
    }

    /**
     * Scope a query to only include employed models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeEmployed($query)
    {
        return $query->whereHas('currentEmployment')
                    ->whereDoesntHave('currentSuspension')
                    ->whereDoesntHave('currentInjury');
    }

    /**
     * Scope a query to only include released models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeReleased($query)
    {
        return $query->whereHas('previousEmployment')
                    ->whereDoesntHave('currentEmployment')
                    ->whereDoesntHave('currentRetirement');
    }

    /**
     * Scope a query to only include unemployed models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeUnemployed($query)
    {
        return $query->whereDoesntHave('currentEmployment');
    }

    /**
     * Scope a query to only include unemployed models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithFirstEmployedAtDate($query)
    {
        return $query->addSelect(['first_employed_at' => Employment::select('started_at')
            ->whereColumn('employable_id', $query->qualifyColumn('id'))
            ->where('employable_type', $this->getMorphClass())
            ->orderBy('started_at', 'desc')
            ->limit(1),
        ])->withCasts(['first_employed_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the models first activation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeOrderByFirstEmployedAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(first_employed_at) $direction");
    }

    /**
     * Scope a query to order by the models current deactivation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeOrderByCurrentDeactivatedAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_released_at) $direction");
    }

    /**
     * Scope a query to only include released models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithReleasedAtDate($query)
    {
        return $query->addSelect(['released_at' => Employment::select('ended_at')
            ->whereColumn('employable_id', $this->getTable().'.id')
            ->where('employable_type', $this->getMorphClass())
            ->orderBy('ended_at', 'desc')
            ->limit(1),
        ])->withCasts(['released_at' => 'datetime']);
    }

    /**
     * Employ a model.
     *
     * @param  Carbon|string $startedAt
     * @return bool
     */
    public function employ($startedAt = null)
    {
        if ($this->canBeEmployed()) {
            $startDate = $startedAt ?? now();

            $this->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startDate]);

            return $this->touch();
        }
    }

    /**
     * Release a model.
     *
     * @param  Carbon|string $releasedAt
     * @return bool
     */
    public function release($releasedAt = null)
    {
        if ($this->isSuspended()) {
            $this->reinstate();
        }

        if ($this->isInjured()) {
            $this->clearFromInjury();
        }

        $releaseDate = $releasedAt ?? now();
        $this->currentEmployment()->update(['ended_at' => $releaseDate]);

        return $this->touch();
    }

    /**
     * Check to see if the model is employed.
     *
     * @return bool
     */
    public function isCurrentlyEmployed()
    {
        return $this->currentEmployment()->exists();
    }

    /**
     * Check to see if the model is employed.
     *
     * @return bool
     */
    public function isUnemployed()
    {
        return $this->employments->isEmpty();
    }

    /**
     * Check to see if the model has a future scheduled employment.
     *
     * @return bool
     */
    public function hasFutureEmployment()
    {
        return $this->futureEmployment()->exists();
    }

    /**
     * Check to see if the model has been released.
     *
     * @return bool
     */
    public function isReleased()
    {
        return $this->previousEmployment()->exists() &&
                $this->currentEmployment()->doesntExist() &&
                $this->currentRetirement()->doesntExist();
    }

    /**
     * Determine if the model can be employed.
     *
     * @return bool
     */
    public function canBeEmployed()
    {
        if ($this->isCurrentlyEmployed()) {
            throw new CannotBeEmployedException('Entity cannot be employed. This entity is currently employed.');
        }

        if ($this->isRetired()) {
            throw new CannotBeEmployedException('Entity cannot be employed. This entity does not have an active employment.');
        }

        return true;
    }

    /**
     * Determine if the model can be released.
     *
     * @return bool
     */
    public function canBeReleased()
    {
        if (! $this->isCurrentlyEmployed() || $this->hasFutureEmployment() || $this->isReleased() || $this->isRetired()) {
            throw new CannotBeEmployedException('Entity cannot be released. This entity does not have an active employment.');
        }

        return true;
    }

    /**
     * Get the model's first employment date.
     *
     * @return string|null
     */
    public function getStartedAtAttribute()
    {
        return optional($this->employments->last())->started_at;
    }
}
