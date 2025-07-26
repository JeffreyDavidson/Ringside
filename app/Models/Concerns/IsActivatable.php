<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Trait for models that have activation lifecycle management.
 *
 * This trait provides comprehensive activation state management for models
 * that can be activated, deactivated, and have activation periods tracked.
 * It includes methods for checking current, future, and historical activation
 * states, plus computed properties for activation status.
 *
 * @template TActivation of Model The activation model class
 *
 * @phpstan-require-implements \App\Models\Contracts\Activatable<TActivation>
 *
 * @example
 * ```php
 * class Wrestler extends Model implements Activatable
 * {
 *     use IsActivatable;
 *
 *     public function activations(): HasMany
 *     {
 *         return $this->hasMany(WrestlerActivation::class);
 *     }
 * }
 *
 * $wrestler = Wrestler::find(1);
 * if ($wrestler->isCurrentlyActivated()) {
 *     echo "Wrestler is currently active";
 * }
 * ```
 */
trait IsActivatable
{
    /**
     * The base activations relationship - must be implemented by the using model.
     *
     * @return HasMany<TActivation, static>
     */
    abstract public function activations(): HasMany;

    /**
     * Get the current active activation for the model.
     *
     * Returns the activation that is currently active (started but not ended).
     *
     * @return HasOne<TActivation, static>
     */
    public function currentActivation(): HasOne
    {
        return $this->activations()
            ->whereNull('ended_at')
            ->one();
    }

    /**
     * Get a future activation that hasn't started yet.
     *
     * Returns an activation that is scheduled to start in the future.
     *
     * @return HasOne<TActivation, static>
     */
    public function futureActivation(): HasOne
    {
        return $this->activations()
            ->whereNull('ended_at')
            ->where('started_at', '>', now())
            ->one();
    }

    /**
     * Get all completed activation periods.
     *
     * Returns activations that have been ended (have an 'ended_at' date).
     *
     * @return HasMany<TActivation, static>
     */
    public function previousActivations(): HasMany
    {
        return $this->activations()
            ->whereNotNull('ended_at');
    }

    /**
     * Get the most recently completed activation.
     *
     * Returns the latest activation that has an 'ended_at' date.
     *
     * @return HasOne<TActivation, static>
     */
    public function previousActivation(): HasOne
    {
        return $this->previousActivations()
            ->latest('ended_at')
            ->one();
    }

    /**
     * Get the very first activation for this model.
     *
     * Returns the activation with the earliest 'started_at' date.
     *
     * @return HasOne<TActivation, static>
     */
    public function firstActivation(): HasOne
    {
        return $this->activations()
            ->one()
            ->ofMany('started_at', 'min');
    }

    /**
     * Check if the model has any activation records.
     */
    public function hasActivations(): bool
    {
        return $this->activations()->count() > 0;
    }

    /**
     * Check if the model is currently activated.
     */
    public function isCurrentlyActivated(): bool
    {
        return $this->currentActivation()->exists();
    }

    /**
     * Check if the model has a future activation scheduled.
     */
    public function hasFutureActivation(): bool
    {
        return $this->futureActivation()->exists();
    }

    /**
     * Check if the model is currently not in an active state.
     *
     * Returns true if the model is deactivated, has only future activations,
     * or is retired.
     */
    public function isNotInActivation(): bool
    {
        if ($this->isDeactivated()) {
            return true;
        }

        if ($this->hasFutureActivation()) {
            return true;
        }

        return (bool) $this->isRetired();
    }

    /**
     * Check if the model has never been activated.
     */
    public function isUnactivated(): bool
    {
        return $this->activations()->count() === 0;
    }

    /**
     * Check if the model is currently deactivated.
     *
     * Returns true if there's a previous activation, no current activation,
     * no future activation, and no current retirement.
     */
    public function isDeactivated(): bool
    {
        return $this->previousActivation()->exists()
            && $this->futureActivation()->doesntExist()
            && $this->currentActivation()->doesntExist()
            && $this->currentRetirement()->doesntExist();
    }

    /**
     * Check if the model was activated on a specific date.
     */
    public function activatedOn(Carbon $activationDate): bool
    {
        return $this->currentActivation ? $this->currentActivation->started_at->eq($activationDate) : false;
    }

    /**
     * Get a formatted string representation of the first activation date.
     */
    public function getFormattedFirstActivation(): string
    {
        return $this->hasActivations() ? ($this->firstActivation?->started_at->format('Y-m-d') ?? 'TBD') : 'TBD';
    }
}
