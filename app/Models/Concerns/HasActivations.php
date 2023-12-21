<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Activation;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

trait HasActivations
{
    /**
     * Get all the activations of the model.
     *
     * @return MorphMany<Activation>
     */
    public function activations(): MorphMany
    {
        return $this->morphMany(Activation::class, 'activatable');
    }

    /**
     * Get the current activation of the model.
     *
     * @return MorphOne<Activation>
     */
    public function currentActivation(): MorphOne
    {
        return $this->morphOne(Activation::class, 'activatable')
            ->where('started_at', '<=', now())
            ->whereNull('ended_at')
            ->latestOfMany();
    }

    /**
     * Get the first activation of the model.
     *
     * @return MorphOne<Activation>
     */
    public function firstActivation(): MorphOne
    {
        return $this->morphOne(Activation::class, 'activatable')
            ->oldestOfMany('started_at');
    }

    /**
     * Get the future activation of the model.
     *
     * @return MorphOne<Activation>
     */
    public function futureActivation(): MorphOne
    {
        return $this->morphOne(Activation::class, 'activatable')
            ->where('started_at', '>', now())
            ->whereNull('ended_at')
            ->latestOfMany();
    }

    /**
     * Get the previous activation of the model.
     *
     * @return MorphOne<Activation>
     */
    public function previousActivation(): MorphOne
    {
        return $this->morphOne(Activation::class, 'activatable')
            ->latest('ended_at')
            ->oldestOfMany();
    }

    /**
     * Get the previous activations of the model.
     *
     * @return MorphMany<Activation>
     */
    public function previousActivations(): MorphMany
    {
        return $this->activations()
            ->whereNotNull('ended_at');
    }

    /**
     * Check to see if the model is currently active.
     */
    public function isCurrentlyActivated(): bool
    {
        return $this->currentActivation()->exists();
    }

    /**
     * Check to see if the model has been activated.
     */
    public function hasActivations(): bool
    {
        return $this->activations()->count() > 0;
    }

    /**
     * Check to see if the model is unactivated.
     */
    public function isUnactivated(): bool
    {
        return $this->activations()->count() === 0;
    }

    /**
     * Check to see if the model is unactivated.
     */
    public function isInactive(): bool
    {
        return $this->currentActivation()->count() === 0;
    }

    /**
     * Check to see if the model has a future activation.
     */
    public function hasFutureActivation(): bool
    {
        return $this->futureActivation()->exists();
    }

    /**
     * Retrieve the model's first activation date.
     *
     * @return Attribute<string, never>
     */
    public function activatedAt(): Attribute
    {
        return new Attribute(
            get: fn () => $this->activations->first()?->started_at
        );
    }

    /**
     * Check to see if the model is not in activation.
     */
    public function isNotActivation(): bool
    {
        return $this->isDeactivated() || $this->hasFutureActivation() || $this->isRetired();
    }

    /**
     * Get the model's first activation date.
     */
    public function activatedOn(Carbon $activationDate): ?bool
    {
        return $this->currentActivation?->started_at->eq($activationDate);
    }

    /**
     * Check to see if activatable can have their start date changed.
     */
    public function canHaveActivationStartDateChanged(Carbon $activationDate): bool
    {
        return $this->hasFutureActivation() && ! $this->activatedOn($activationDate);
    }

    /**
     * Check to see if the model is deactivated.
     */
    public function isDeactivated(): bool
    {
        return $this->previousActivation()->exists()
                && $this->currentActivation()->doesntExist()
                && $this->futureActivation()->doesntExist()
                && $this->currentRetirement()->doesntExist();
    }

    /**
     * Check to see if the model is not in activation.
     */
    public function isNotInActivation(): bool
    {
        return $this->isNotActivation() || $this->isDeactivated() || $this->hasFutureActivation() || $this->isRetired();
    }
}
