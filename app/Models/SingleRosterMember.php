<?php

declare(strict_types=1);

namespace App\Models;

use App\Builders\SingleRosterMemberQueryBuilder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

abstract class SingleRosterMember extends RosterMember
{
    /**
     * Create a new Eloquent query builder for the model.
     */
    public function newEloquentBuilder($query): SingleRosterMemberQueryBuilder
    {
        return new SingleRosterMemberQueryBuilder($query);
    }

    public function canBeEmployed(): bool
    {
        if ($this->isCurrentlyEmployed()) {
            return false;
        }

        if ($this->isRetired()) {
            return false;
        }

        return true;
    }

    public function canBeSuspended(): bool
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

    public function canBeReinstated(): bool
    {
        return $this->isSuspended();
    }

    public function canBeUnretired(): bool
    {
        if (! $this->isRetired()) {
            return false;
        }

        return false;
    }

    /**
     * Get the injuries of the model.
     */
    public function injuries(): MorphMany
    {
        return $this->morphMany(Injury::class, 'injurable');
    }

    /**
     * Get the current injury of the model.
     */
    public function currentInjury(): MorphOne
    {
        return $this->morphOne(Injury::class, 'injurable')
            ->whereNull('ended_at')
            ->limit(1);
    }

    /**
     * Get the previous injuries of the model.
     */
    public function previousInjuries(): MorphMany
    {
        return $this->injuries()
            ->whereNotNull('ended_at');
    }

    /**
     * Get the previous injury of the model.
     */
    public function previousInjury(): MorphOne
    {
        return $this->morphOne(Injury::class, 'injurable')
            ->latest('ended_at')
            ->limit(1);
    }

    /**
     * Check to see if the model is injured.
     */
    public function isInjured(): bool
    {
        return $this->currentInjury()->exists();
    }

    /**
     * Check to see if the model has been employed.
     */
    public function hasInjuries(): bool
    {
        return $this->injuries()->count() > 0;
    }

    /**
     * Determine if the model can be injured.
     */
    public function canBeInjured(): bool
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
     */
    public function canBeClearedFromInjury(): bool
    {
        if (! $this->isInjured()) {
            return false;
        }

        return true;
    }

    /**
     * Check to see if the model is bookable.
     */
    public function isBookable(): bool
    {
        if ($this->isNotInEmployment() || $this->isSuspended() || $this->isInjured() || $this->hasFutureEmployment()) {
            return false;
        }

        return true;
    }
}
