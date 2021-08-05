<?php

namespace App\Models\Contracts;

interface Reinstatable
{
    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function suspensions();

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function currentSuspension();

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function previousSuspension();

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function previousSuspensions();

    /**
     * Set the token value for the "remember me" session.
     *
     * @return void
     */
    public function scopeSuspended($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeWithCurrentSuspendedAtDate($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeOrderByCurrentSuspendedAtDate($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function isSuspended();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function hasSuspensions();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function canBeSuspended();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function canBeReinstated();
}
