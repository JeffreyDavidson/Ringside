<?php

namespace App\Models;

use App\Builders\SingleRosterMemberQueryBuilder;

abstract class SingleRosterMember extends RosterMember
{
    /**
     * Create a new Eloquent query builder for the model.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \App\Builders\SingleRosterMemberQueryBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new SingleRosterMemberQueryBuilder($query);
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
     * Determine if the model can be suspended.
     *
     * @return bool
     */
    public function canBeSuspended()
    {
        if ($this->isNotInEmployment() || $this->hasFutureEmployment()) {
            return false;
        }

        if ($this->isSuspended()) {
            return false;
        }

        if ($this->isInjured()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the tag team can be reinstated.
     *
     * @return bool
     */
    public function canBeReinstated()
    {
        return $this->isSuspended();
    }

    /**
     * Determine if the model can be unretired.
     *
     * @return bool
     */
    public function canBeUnretired()
    {
        if (! $this->isRetired()) {
            return false;
        }

        return true;
    }

    /**
     * Get the injuries of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function injuries()
    {
        return $this->morphMany(Injury::class, 'injurable');
    }

    /**
     * Get the current injury of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentInjury()
    {
        return $this->morphOne(Injury::class, 'injurable')
            ->whereNull('ended_at')
            ->limit(1);
    }

    /**
     * Get the previous injuries of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousInjuries()
    {
        return $this->injuries()
            ->whereNotNull('ended_at');
    }

    /**
     * Get the previous injury of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousInjury()
    {
        return $this->morphOne(Injury::class, 'injurable')
            ->latest('ended_at')
            ->limit(1);
    }

    /**
     * Check to see if the model is injured.
     *
     * @return bool
     */
    public function isInjured()
    {
        return $this->currentInjury()->exists();
    }

    /**
     * Check to see if the model has been employed.
     *
     * @return bool
     */
    public function hasInjuries()
    {
        return $this->injuries()->count() > 0;
    }

    /**
     * Determine if the model can be injured.
     *
     * @return bool
     */
    public function canBeInjured()
    {
        if ($this->isNotInEmployment() || $this->hasFutureEmployment()) {
            return false;
        }

        if ($this->isInjured()) {
            return false;
        }

        if ($this->isSuspended()) {
            return false;
        }

        return true;
    }

    /**
     * Determine if the model can be cleared from an injury.
     *
     * @return bool
     */
    public function canBeClearedFromInjury()
    {
        if (! $this->isInjured()) {
            return false;
        }

        return true;
    }

    /**
     * Check to see if the model is bookable.
     *
     * @return bool
     */
    public function isBookable()
    {
        if ($this->isNotInEmployment() || $this->isSuspended() || $this->isInjured() || $this->hasFutureEmployment()) {
            return false;
        }

        return true;
    }
}
