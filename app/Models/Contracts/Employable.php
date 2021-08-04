<?php

namespace App\Models\Contracts;

interface Employable
{
    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function employments();

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function currentEmployment();

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function futureEmployment();

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function previousEmployments();

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function previousEmployment();

    /**
     * Set the token value for the "remember me" session.
     *
     * @return void
     */
    public function scopeEmployed($query);

     /**
     * Set the token value for the "remember me" session.
     *
     * @return void
     */
    public function scopeFutureEmployed($query);

     /**
     * Set the token value for the "remember me" session.
     *
     * @return void
     */
    public function scopeReleased($query);

     /**
     * Set the token value for the "remember me" session.
     *
     * @return void
     */
    public function scopeUnemployed($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeWithFirstEmployedAtDate($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeOrderByFirstEmployedAtDate($query);

     /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeWithReleasedAtDate($query);

     /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function scopeOrderByCurrentReleasedAtDate($query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function isCurrentlyEmployed();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function hasEmployments();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function isNotInEmployment();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function isUnemployed();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function hasFutureEmployment();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function isReleased();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function canBeEmployed();

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function canBeReleased();
}
