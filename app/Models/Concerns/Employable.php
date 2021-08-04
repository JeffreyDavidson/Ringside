<?php

namespace App\Models\Concerns;

use App\Models\Employment;

trait Employable
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
     * Get the first activation of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function firstEmployment()
    {
        return $this->morphOne(Employment::class, 'activatable')
                    ->oldestOfMany('started_at');
    }

    /**
     * Get the current employment of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentEmployment()
    {
        return $this->morphOne(Employment::class, 'employable')
                    ->where('started_at', '<=', now())
                    ->where('ended_at', '=', null)
                    ->latestOfMany();
    }

    /**
     * Get the future employment of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function futureEmployment()
    {
        return $this->morphOne(Employment::class, 'employable')
                    ->where('started_at', '>', now())
                    ->whereNull('ended_at')
                    ->latestOfMany();
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
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousEmployment()
    {
        return $this->morphOne(Employment::class, 'employable')
                    ->whereNotNull('ended_at')
                    ->latest('ended_at')
                    ->latestOfMany();
    }

    /**
     * Scope a query to only include future employed models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFutureEmployed($query)
    {
        return $query->whereHas('futureEmployment');
    }

    /**
     * Scope a query to only include employed referees.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
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
     * @return \Illuminate\Database\Eloquent\Builder
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
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnemployed($query)
    {
        return $query->whereDoesntHave('currentEmployment')
                    ->orWhereDoesntHave('previousEmployments');
    }

    /**
     * Scope a query to include first employment date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithFirstEmployedAtDate($query)
    {
        return $query->addSelect(['first_employed_at' => Employment::select('started_at')
            ->whereColumn('employable_id', $query->qualifyColumn('id'))
            ->where('employable_type', $this->getMorphClass())
            ->oldest('started_at')
            ->limit(1),
        ])->withCasts(['first_employed_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's first employment date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByFirstEmployedAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(first_employed_at) $direction");
    }

    /**
     * Scope a query to include released date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithReleasedAtDate($query)
    {
        return $query->addSelect(['released_at' => Employment::select('ended_at')
            ->whereColumn('employable_id', $this->getTable().'.id')
            ->where('employable_type', $this->getMorphClass())
            ->latest('ended_at')
            ->limit(1),
        ])->withCasts(['released_at' => 'datetime']);
    }

    /**
     * Scope a query to order by the model's current released date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  string $direction
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCurrentReleasedAtDate($query, $direction = 'asc')
    {
        return $query->orderByRaw("DATE(current_released_at) $direction");
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
     * Check to see if the model has been employed.
     *
     * @return bool
     */
    public function hasEmployments()
    {
        return $this->employments()->count() > 0;
    }

    /**
     * Check to see if the model is not in employment.
     *
     * @return bool
     */
    public function isNotInEmployment()
    {
        return $this->isUnemployed() || $this->isReleased() || $this->hasFutureEmployment() || $this->isRetired();
    }

    /**
     * Check to see if the model is unemployed.
     *
     * @return bool
     */
    public function isUnemployed()
    {
        return $this->employments()->count() === 0;
    }

    /**
     * Check to see if the model has a future employment.
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
                $this->futureEmployment()->doesntExist() &&
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
            return false;
        }

        if ($this->isRetired()) {
            return false;
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
        if ($this->isNotInEmployment()) {
            return false;
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
        return optional($this->employments->first())->started_at;
    }
}
