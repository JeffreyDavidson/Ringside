<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Retirement;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasRetirements
{
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
     * Retrieve the retirement start date.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function retiredAt(): Attribute
    {
        return new Attribute(
            get: fn () => $this->currentRetirement?->started_at
        );
    }
}
