<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

interface Activatable
{
    /**
     * Get all of the activations of the model.
     */
    public function activations(): MorphMany;

    /**
     * Get the current activation of the model.
     */
    public function currentActivation(): MorphOne;

    /**
     * Get the first activation of the model.
     */
    public function firstActivation(): MorphOne;

    /**
     * Get the future activation of the model.
     */
    public function futureActivation(): MorphOne;

    /**
     * Get the previous activation of the model.
     */
    public function previousActivation(): MorphOne;

    /**
     * Get the previous activations of the model.
     */
    public function previousActivations(): MorphMany;

    /**
     * Check to see if the model is currently active.
     */
    public function isCurrentlyActivated(): bool;

    /**
     * Check to see if the model has been activated.
     */
    public function hasActivations(): bool;

    /**
     * Check to see if the model is unactivated.
     */
    public function isUnactivated(): bool;

    /**
     * Check to see if the model has a future activation.
     */
    public function hasFutureActivation(): bool;

    /**
     * Retrieve the model's first activation date.
     */
    public function activatedAt(): Attribute;

    /**
     * Check to see if the model is not in activation.
     */
    public function isNotInActivation(): bool;

    /**
     * Get the model's first activation date.
     */
    public function activatedOn(Carbon $activationDate): ?bool;

    /**
     * Check to see if the model is currently deactivated.
     */
    public function isDeactivated(): bool;
}
