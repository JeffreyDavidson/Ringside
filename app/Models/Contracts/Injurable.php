<?php

namespace App\Models\Contracts;

interface Injurable
{
    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function injuries();

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function currentInjury();

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function previousInjury();

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function previousInjuries();

    /**
     * Set the token value for the "remember me" session.
     *
     * @return void
     */
    public function scopeInjured($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeWithCurrentInjuredAtDate($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeOrderByCurrentInjuredAtDate($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function isInjured();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function hasInjuries();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function canBeInjured();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function canBeClearedFromInjury();
}
