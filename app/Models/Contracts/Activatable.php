<?php

namespace App\Models\Contracts;

interface Activatable
{
    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function activations();

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function currentActivation();

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function firstActivation();

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function futureActivation();

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function previousActivation();

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function previousActivations();

    /**
     * Set the token value for the "remember me" session.
     *
     * @return void
     */
    public function scopeActivated($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeFutureActivation($scope);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeInactive($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeDeactivated($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeWithFirstActivatedAtDate($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeWithLastDeactivatedAtDate($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeOrderByFirstActivatedAtDate($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeOrderByLastDeactivatedAtDate($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function isCurrentlyActivated();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function hasActivations();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function isNotActivated();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function hasFutureActivation();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function isDeactivated();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function canBeActivated();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getActivatedAtAttribute();
}
