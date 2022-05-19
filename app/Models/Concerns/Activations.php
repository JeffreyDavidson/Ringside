<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Activation;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;

trait Activations
{
    /**
     * Get all of the activations of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function activations()
    {
        return $this->morphMany(Activation::class, 'activatable');
    }

    /**
     * Get the current activation of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentActivation()
    {
        return $this->morphOne(Activation::class, 'activatable')
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
            ->latestOfMany();
    }

    /**
     * Get the first activation of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function firstActivation()
    {
        return $this->morphOne(Activation::class, 'activatable')
            ->oldestOfMany('started_at');
    }

    /**
     * Get the future activation of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function futureActivation()
    {
        return $this->morphOne(Activation::class, 'activatable')
            ->where('started_at', '>', now())
            ->whereNull('ended_at')
            ->latestOfMany();
    }

    /**
     * Get the previous activation of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousActivation()
    {
        return $this->morphOne(Activation::class, 'activatable')
            ->latest('ended_at')
            ->oldestOfMany();
    }

    /**
     * Get the previous activations of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousActivations()
    {
        return $this->activations()
            ->whereNotNull('ended_at');
    }

    /**
     * Check to see if the model is currently active.
     *
     * @return bool
     */
    public function isCurrentlyActivated()
    {
        return $this->currentActivation()->exists();
    }

    /**
     * Check to see if the model has been activated.
     *
     * @return bool
     */
    public function hasActivations()
    {
        return $this->activations()->count() > 0;
    }

    /**
     * Check to see if the model is unactivated.
     *
     * @return bool
     */
    public function isUnactivated()
    {
        return $this->activations()->count() === 0;
    }

    /**
     * Check to see if the model is unactivated.
     *
     * @return bool
     */
    public function isInactive()
    {
        return $this->current()->count() === 0;
    }

    /**
     * Check to see if the model has a future activation.
     *
     * @return bool
     */
    public function hasFutureActivation()
    {
        return $this->futureActivation()->exists();
    }

    /**
     * Determine if the model can be activated.
     *
     * @return bool
     */
    public function canBeActivated()
    {
        return $this->isUnactivated() || $this->hasFutureActivation() || $this->isDeactivated();
    }

    /**
     * Retrieve the model's first activation date.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function activatedAt(): Attribute
    {
        return new Attribute(
            get: fn () => $this->activations->first()?->started_at
        );
    }

    /**
     * Check to see if the model is not in activation.
     *
     * @return bool
     */
    public function isNotActivation()
    {
        return $this->isDeactivated() || $this->hasFutureActivation() || $this->isRetired();
    }

    /**
     * Get the model's first activation date.
     *
     * @param  \Illuminate\Support\Carbon $activationDate
     * @return bool|null
     */
    public function activatedOn(Carbon $activationDate)
    {
        return $this->currentActivation?->started_at->eq($activationDate);
    }

    /**
     * Check to see if activatable can have their start date changed.
     *
     * @param  \Illuminate\Support\Carbon $activationDate
     * @return bool
     */
    public function canHaveActivationStartDateChanged(Carbon $activationDate)
    {
        return $this->hasFutureActivation() && ! $this->activatedOn($activationDate);
    }
}
