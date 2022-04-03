<?php

namespace App\Models\Contracts;

use Carbon\Carbon;

interface Activatable
{
    /**
     * Get all of the activations of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function activations();

    /**
     * Get the current activation of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentActivation();

    /**
     * Get the first activation of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function firstActivation();

    /**
     * Get the future activation of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function futureActivation();

    /**
     * Get the previous activation of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousActivation();

    /**
     * Get the previous activations of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousActivations();

    /**
     * Check to see if the model is currently active.
     *
     * @return bool
     */
    public function isCurrentlyActivated();

    /**
     * Check to see if the model has been activated.
     *
     * @return bool
     */
    public function hasActivations();

    /**
     * Check to see if the model is unactivated.
     *
     * @return bool
     */
    public function isUnactivated();

    /**
     * Check to see if the model has a future activation.
     *
     * @return bool
     */
    public function hasFutureActivation();

    /**
     * Determine if the model can be activated.
     *
     * @return bool
     */
    public function canBeActivated();

    /**
     * Retrieve the model's first activation date.
     *
     * @return \Illuminate\Database\Eloquent\Casts\Attribute
     */
    public function activatedAt();

    /**
     * Check to see if the model is not in activation.
     *
     * @return bool
     */
    public function isNotInActivation();

    /**
     * Check to see if the model was activated on a given date.
     *
     * @param  \Carbon\Carbon  $activationDate
     * @return bool
     */
    public function activatedOn(Carbon $activationDate);
}
