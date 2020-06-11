<?php

namespace App\Models\Concerns;

use App\Models\Employment;
use App\Traits\HasCachedAttributes;

trait CanBeEmployed
{
    public static function bootCanBeEmployed()
    {
        if (config('app.debug')) {
            $traits = class_uses_recursive(static::class);

            if (!in_array(HasCachedAttributes::class, $traits)) {
                throw new \LogicException('CanBeRetired trait used without HasCachedAttributes trait');
            }
        }
    }

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
        return $this->morphOne(Employment::class, 'employable')
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
        return $this->morphOne(Employment::class, 'employable')
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
     * Scope a query to only include pending employment models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopePendingEmployment($query)
    {
        return $query->whereHas('futureEmployment')
            ->with('employments')
            ->withEmployedAtDate();
    }

    /**
     * Scope a query to only include employed models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeEmployed($query)
    {
        return $query->whereHas('currentEmployment')
            ->with('employments')
            ->withEmployedAtDate();
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
        return $query->whereDoesntHave('employments');
    }

    /**
     * Scope a query to only include unemployed models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithEmployedAtDate($query)
    {
        return $query->addSelect(['employed_at' => Employment::select('started_at')
            ->whereColumn('employable_id', $this->getTable().'.id')
            ->where('employable_type', $this->getMorphClass())
            ->orderBy('started_at', 'desc')
            ->limit(1)
        ])->withCasts(['employed_at' => 'datetime']);
    }

    /**
     * Scope a query to only include unemployed models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithReleasedAtDate($query)
    {
        return $query->addSelect(['released_at' => Employment::select('ended_at')
            ->whereColumn('employable_id', $this->getTable().'.id')
            ->where('employable_type', $this->getMorphClass())
            ->orderBy('ended_at', 'desc')
            ->limit(1)
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
        $startDate = $startedAt ?? now();

        $this->employments()->updateOrCreate(['ended_at' => null], ['started_at' => $startDate]);

        return $this->touch();
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
     * Check to see if the model is activated.
     *
     * @return bool
     */
    public function isPendingEmployment()
    {
        return $this->futureEmployment()->exists();
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
        if ($this->isUnemployed()) {
            return false;
        }

        if ($this->hasFutureEmployment()) {
            return false;
        }

        if ($this->isReleased()) {
            return false;
        }

        if ($this->isRetired()) {
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
        return optional($this->employments->last())->started_at;
    }

    /**
    * Get the current employment of the model.
    *
    * @return App\Models\Employment
    */
    public function getCurrentEmploymentAttribute()
    {
        if (! $this->relationLoaded('currentEmployment')) {
            $this->setRelation('currentEmployment', $this->currentEmployment()->get());
        }

        return $this->getRelation('currentEmployment')->first();
    }

    /**
     * Get the previous employment of the model.
     *
     * @return App\Models\Employment
     */
    public function getPreviousEmploymentAttribute()
    {
        if (! $this->relationLoaded('previousEmployment')) {
            $this->setRelation('previousEmployment', $this->previousEmployment()->get());
        }

        return $this->getRelation('previousEmployment')->first();
    }

    /**
     * Get the previous employment of the model.
     *
     * @return App\Models\Employment
     */
    public function getFutureEmploymentAttribute()
    {
        if (! $this->relationLoaded('futureEmployment')) {
            $this->setRelation('futureEmployment', $this->futureEmployment()->get());
        }

        return $this->getRelation('futureEmployment')->first();
    }
}
