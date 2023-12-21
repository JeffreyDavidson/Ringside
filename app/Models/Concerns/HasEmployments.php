<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Employment;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

trait HasEmployments
{
    /**
     * Get all the employments of the model.
     *
     * @return MorphMany<Employment>
     */
    public function employments(): MorphMany
    {
        return $this->morphMany(Employment::class, 'employable');
    }

    /**
     * Get the first employment of the model.
     *
     * @return MorphOne<Employment>
     */
    public function firstEmployment(): MorphOne
    {
        return $this->morphOne(Employment::class, 'employable')
            ->oldestOfMany('started_at');
    }

    /**
     * Get the current employment of the model.
     *
     * @return MorphOne<Employment>
     */
    public function currentEmployment(): MorphOne
    {
        return $this->morphOne(Employment::class, 'employable')
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
            ->latestOfMany();
    }

    /**
     * Get the future employment of the model.
     *
     * @return MorphOne<Employment>
     */
    public function futureEmployment(): MorphOne
    {
        return $this->morphOne(Employment::class, 'employable')
            ->where('started_at', '>', now())
            ->whereNull('ended_at')
            ->latestOfMany();
    }

    /**
     * Get the previous employments of the model.
     *
     * @return MorphMany<Employment>
     */
    public function previousEmployments(): MorphMany
    {
        return $this->employments()
            ->whereNotNull('ended_at');
    }

    /**
     * Get the previous employment of the model.
     *
     * @return MorphOne<Employment>
     */
    public function previousEmployment(): MorphOne
    {
        return $this->morphOne(Employment::class, 'employable')
            ->whereNotNull('ended_at')
            ->latest('ended_at')
            ->latestOfMany();
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
