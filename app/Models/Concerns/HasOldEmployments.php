<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Employment;
use App\Models\WrestlerEmployment;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

trait HasOldEmployments
{
    /**
     * Get all the employments of the model.
     *
     * @return HasMany<WrestlerEmployment>
     */
    public function employments(): HasMany
    {
        return $this->hasMany(WrestlerEmployment::class);
    }

    /**
     * Check to see if the model is employed.
     */
    public function isCurrentlyEmployed(): bool
    {
        return $this->currentEmployment()->exists();
    }

    /**
     * Check to see if the model is employed.
     */
    public function startDateWas(Carbon $startDate): bool
    {
        if (is_null($this->firstEmployment)) {
            return false;
        }

        return $this->firstEmployment->started_at->ne($startDate);
    }

    /**
     * Check to see if the model has been employed.
     */
    public function hasEmployments(): bool
    {
        return $this->employments()->count() > 0;
    }

    /**
     * Check to see if the model is not in employment.
     */
    public function isNotInEmployment(): bool
    {
        return $this->isUnemployed() || $this->isReleased() || $this->isRetired();
    }

    /**
     * Check to see if the model is unemployed.
     */
    public function isUnemployed(): bool
    {
        return $this->employments()->count() === 0;
    }

    /**
     * Check to see if the model has a future employment.
     */
    public function hasFutureEmployment(): bool
    {
        return $this->futureEmployment()->exists();
    }

    /**
     * Check to see if the model has been released.
     */
    public function isReleased(): bool
    {
        return $this->previousEmployment()->exists()
                && $this->futureEmployment()->doesntExist()
                && $this->currentEmployment()->doesntExist()
                && $this->currentRetirement()->doesntExist();
    }

    /**
     * Get the model's first employment date.
     */
    public function startedAt(): Attribute
    {
        return new Attribute(
            get: fn () => $this->firstEmployment?->started_at
        );
    }

    /**
     * Determine if the roster member was employed on a given date.
     */
    public function employedOn(Carbon $employmentDate): bool
    {
        if (is_null($this->currentEmployment)) {
            return false;
        }

        return $this->currentEmployment->started_at->eq($employmentDate);
    }

    /**
     * Determine if the roster member is to be employed on a given date.
     */
    public function scheduledToBeEmployedOn(Carbon $employmentDate): bool
    {
        if (is_null($this->futureEmployment)) {
            return false;
        }

        return $this->futureEmployment->started_at->eq($employmentDate);
    }

    /**
     * Determine if the roster member is to be employed on a given date.
     */
    public function employedBefore(Carbon $employmentDate): bool
    {
        if (is_null($this->currentEmployment)) {
            return false;
        }

        return $this->currentEmployment->started_at->lte($employmentDate);
    }

    /**
     * Determine if the roster member is employed after a given date.
     */
    public function employedAfter(Carbon $employmentDate): bool
    {
        if (is_null($this->currentEmployment)) {
            return false;
        }

        return $this->currentEmployment->started_at->gt($employmentDate);
    }

    /**
     * Determine if the roster member future start date is before the given date.
     */
    public function futureEmploymentIsBefore(Carbon $date): bool
    {
        if (is_null($this->futureEmployment)) {
            return false;
        }

        return $this->futureEmployment->started_at->lt($date);
    }
}
