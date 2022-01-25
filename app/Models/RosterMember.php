<?php

namespace App\Models;

use App\Builders\RosterMemberQueryBuilder;
use App\Models\Contracts\Employable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

abstract class RosterMember extends Model implements Employable
{
    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     *
     * @return \App\Builders\RosterMemberQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new RosterMemberQueryBuilder($query);
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
     * @param  \Carbon\Carbon $startDate
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
     * @param  \Carbon\Carbon $employmentDate
     *
     * @return bool
     */
    public function employedOn(Carbon $employmentDate)
    {
        return $this->currentEmployment->started_at->eq($employmentDate);
    }

    /**
     * Determine if the roster member is to be employed on a given date.
     *
     * @param  \Carbon\Carbon $employmentDate
     *
     * @return bool
     */
    public function scheduledToBeEmployedOn(Carbon $employmentDate)
    {
        return $this->futureEmployment->started_at->eq($employmentDate);
    }

    /**
     * Determine if the roster member is to be employed on a given date.
     *
     * @param  \Carbon\Carbon $employmentDate
     *
     * @return bool
     */
    public function employedBefore(Carbon $employmentDate)
    {
        return $this->currentEmployment->started_at->lte($employmentDate);
    }

    /**
     * Determine if the roster member is employed after a given date.
     *
     * @param  \Carbon\Carbon $employmentDate
     *
     * @return bool
     */
    public function employedAfter(Carbon $employmentDate)
    {
        return $this->currentEmployment->started_at->gt($employmentDate);
    }

    /**
     * Determine if the roster member future start date is before the given date.
     *
     * @param  \Carbon\Carbon $date
     *
     * @return bool
     */
    public function futureEmploymentIsBefore(Carbon $date)
    {
        return $this->futureEmployment->started_at->lt($date);
    }

    /**
     * Check to see if employable can have their start date changed.
     *
     * @param  \Carbon\Carbon $employmentDate
     *
     * @return bool
     */
    public function canHaveEmploymentStartDateChanged(Carbon $employmentDate)
    {
        return $this->hasFutureEmployment() && ! $this->scheduledToBeEmployedOn($employmentDate);
    }

    /**
     * Get the suspensions of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function suspensions()
    {
        return $this->morphMany(Suspension::class, 'suspendable');
    }

    /**
     * Get the current suspension of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentSuspension()
    {
        return $this->morphOne(Suspension::class, 'suspendable')
            ->whereNull('ended_at')
            ->limit(1);
    }

    /**
     * Get the current suspension of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousSuspensions()
    {
        return $this->suspensions()
            ->whereNotNull('ended_at');
    }

    /**
     * Get the previous suspension of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousSuspension()
    {
        return $this->morphOne(Suspension::class, 'suspendable')
            ->latest('ended_at')
            ->limit(1);
    }

    /**
     * Check to see if the model has been suspended.
     *
     * @return bool
     */
    public function isSuspended()
    {
        return $this->currentSuspension()->exists();
    }

    /**
     * Check to see if the model has been suspended.
     *
     * @return bool
     */
    public function hasSuspensions()
    {
        return $this->suspensions()->count() > 0;
    }

    /**
     * Determine if the model can be reinstated.
     *
     * @return bool
     */
    public function canBeReinstated()
    {
        if (! $this->isSuspended()) {
            return false;
        }

        return true;
    }

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
        return $this->morphOne(Retirement::class, 'retiree')
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
     * Get the previous retirement of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousRetirement()
    {
        return $this->morphOne(Retirement::class, 'retiree')
            ->latest('ended_at')
            ->limit(1);
    }

    /**
     * Check to see if the model is retired.
     *
     * @return bool
     */
    public function isRetired()
    {
        return $this->currentRetirement()->exists();
    }

    /**
     * Check to see if the model has been activated.
     *
     * @return bool
     */
    public function hasRetirements()
    {
        return $this->retirements()->count() > 0;
    }

    /**
     * Determine if the model can be retired.
     *
     * @return bool
     */
    public function canBeRetired()
    {
        if ($this->isNotInEmployment() || $this->hasFutureEmployment()) {
            return false;
        }

        return true;
    }
}
