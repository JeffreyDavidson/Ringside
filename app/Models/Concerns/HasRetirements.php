<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Models\Retirement;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasRetirements
{
    /**
     * Get the retirements of the model.
     */
    public function retirements(): MorphMany
    {
        return $this->morphMany(Retirement::class, 'retiree');
    }

    /**
     * Get the current retirement of the model.
     */
    public function currentRetirement(): MorphOne
    {
        return $this->morphOne(Retirement::class, 'retiree')
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
            ->limit(1);
    }

    /**
     * Get the previous retirements of the model.
     */
    public function previousRetirements(): MorphMany
    {
        return $this->retirements()
            ->whereNotNull('ended_at');
    }

    /**
     * Get the previous retirement of the model.
     */
    public function previousRetirement(): MorphOne
    {
        return $this->morphOne(Retirement::class, 'retiree')
            ->latest('ended_at')
            ->limit(1);
    }

    /**
     * Check to see if the model is retired.
     */
    public function isRetired(): bool
    {
        return $this->currentRetirement()->exists();
    }

    /**
     * Check to see if the model has been activated.
     */
    public function hasRetirements(): bool
    {
        return $this->retirements()->count() > 0;
    }

    public function canBeRetired()
    {
        if ($this->isNotInEmployment()) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve the retirement start date.
     */
    public function retiredAt(): Attribute
    {
        return new Attribute(
            get: fn () => $this->currentRetirement?->started_at
        );
    }
}
