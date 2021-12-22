<?php

namespace App\Models\Concerns;

use App\Models\Activation;

trait Activatable
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
                        ->where('ended_at', '=', null)
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
        if ($this->isCurrentlyActivated()) {
            // throw new CannotBeActivatedException('Entity cannot be activated. This entity is active.');
            return false;
        }

        if ($this->isRetired()) {
            // throw new CannotBeActivatedException('Entity cannot be activated. This entity is retired.');
            return false;
        }

        return true;
    }

    /**
     * Retrieve the model's first activation date.
     *
     * @return string|null
     */
    public function getActivatedAtAttribute()
    {
        return $this->activations->first()?->started_at;
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
     * @param  string $activationDate
     * @return bool
     */
    public function activatedOn(string $activationDate)
    {
        return $this->activations->last()->started_at->ne($activationDate);
    }

    /**
     * Check to see if activatable can have their start date changed.
     *
     * @return bool
     */
    public function canHaveActivationStartDateChanged()
    {
        if ($this->isUnactivated() || $this->hasFutureActivation()) {
            return true;
        }

        return false;
    }
}
