<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Retirement;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasRetirements
{
    /**
     * Get the retirements of the model.
     *
     * @return MorphMany<Retirement>
     */
    public function retirements(): MorphMany
    {
        return $this->morphMany(Retirement::class, 'retiree');
    }

    /**
     * Get the current retirement of the model.
     *
     * @return MorphOne<Retirement>
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
     *
     * @return MorphMany<Retirement>
     */
    public function previousRetirements(): MorphMany
    {
        return $this->retirements()
            ->whereNotNull('ended_at');
    }

    /**
     * Get the previous retirement of the model.
     *
     * @return MorphOne<Retirement>
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

    /**
     * Retrieve the retirement start date.
     *
     * @return Attribute<string, never>
     */
    public function retiredAt(): Attribute
    {
        return new Attribute(
            get: fn () => $this->currentRetirement?->started_at
        );
    }
}
