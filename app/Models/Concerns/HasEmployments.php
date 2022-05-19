<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Employment;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasEmployments
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
     * Get the first employment of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function firstEmployment()
    {
        return $this->morphOne(Employment::class, 'employable')
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
            ->whereNull('ended_at')
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
     * @param  \Illuminate\Support\Carbon $startDate
     * @return bool
     */
    public function startDateWas(Carbon $startDate)
    {
        return $this->firstEmployment->started_at->ne($startDate);
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
        return $this->isUnemployed() || $this->isReleased() || $this->isRetired();
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
        return $this->previousEmployment()->exists()
                && $this->futureEmployment()->doesntExist()
                && $this->currentEmployment()->doesntExist()
                && $this->currentRetirement()->doesntExist();
    }

    /**
     * Determine if the model can be released.
     *
     * @return bool
     */
    public function canBeReleased()
    {
        if ($this->isNotInEmployment() || $this->hasFutureEmployment()) {
            return false;
        }

        return true;
    }

    /**
     * Get the model's first employment date.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function startedAt(): Attribute
    {
        return new Attribute(
            get: fn () => $this->firstEmployment?->started_at
        );
    }

    /**
     * Determine if the roster member was employed on a given date.
     *
     * @param  \Illuminate\Support\Carbon $employmentDate
     * @return bool
     */
    public function employedOn(Carbon $employmentDate)
    {
        return $this->currentEmployment->started_at->eq($employmentDate);
    }

    /**
     * Determine if the roster member is to be employed on a given date.
     *
     * @param  \Illuminate\Support\Carbon $employmentDate
     * @return bool
     */
    public function scheduledToBeEmployedOn(Carbon $employmentDate)
    {
        return $this->futureEmployment->started_at->eq($employmentDate);
    }

    /**
     * Determine if the roster member is to be employed on a given date.
     *
     * @param  \Illuminate\Support\Carbon $employmentDate
     * @return bool
     */
    public function employedBefore(Carbon $employmentDate)
    {
        return $this->currentEmployment->started_at->lte($employmentDate);
    }

    /**
     * Determine if the roster member is employed after a given date.
     *
     * @param  \Illuminate\Support\Carbon $employmentDate
     * @return bool
     */
    public function employedAfter(Carbon $employmentDate)
    {
        return $this->currentEmployment->started_at->gt($employmentDate);
    }

    /**
     * Determine if the roster member future start date is before the given date.
     *
     * @param  \Illuminate\Support\Carbon $date
     * @return bool
     */
    public function futureEmploymentIsBefore(Carbon $date)
    {
        return $this->futureEmployment->started_at->lt($date);
    }

    /**
     * Check to see if employable can have their start date changed.
     *
     * @param  \Illuminate\Support\Carbon $employmentDate
     * @return bool
     */
    public function canHaveEmploymentStartDateChanged(Carbon $employmentDate)
    {
        return $this->hasFutureEmployment() && ! $this->scheduledToBeEmployedOn($employmentDate);
    }
}
