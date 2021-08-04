<?php

namespace App\Models\Contracts;

interface Retirable
{
    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function retirements();

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function currentRetirement();

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function previousRetirements();

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function previousRetirement();

    /**
     * Set the token value for the "remember me" session.
     *
     * @return void
     */
    public function scopeRetired($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeWithCurrentRetiredAtDate($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeOrderByCurrentRetiredAtDate($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function isRetired();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function hasRetirements();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function canBeRetired();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function canBeUnretired();
}
