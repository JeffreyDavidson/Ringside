<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Retirable
{
    /**
     * Get the retirements of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function retirements();

    /**
     * Get the current retirement of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentRetirement();

    /**
     * Get the previous retirements of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousRetirements();

    /**
     * Get the previous retirement of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousRetirement();

    /**
     * Scope a query to include retired models.
     *
     * @param   \Illuminate\Database\Eloquent\Builder  $query
     * @return  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRetired(Builder $query);

    /**
     * Scope a query to include model's current retired at date.
     *
     * @param   \Illuminate\Database\Eloquent\Builder  $query
     * @return  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCurrentRetiredAtDate(Builder $query);

    /**
     * Scope a query to order models by current retired at date.
     *
     * @param   \Illuminate\Database\Eloquent\Builder  $query
     * @return  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCurrentRetiredAtDate(Builder $query);

    /**
     * Get the column name for the "remember me" token.
     *
     * @return bool
     */
    public function isRetired();

    /**
     * Check to see if the model has been retired.
     *
     * @return bool
     */
    public function hasRetirements();

    /**
     * Determine if a model can be retired.
     *
     * @return bool
     */
    public function canBeRetired();

    /**
     * Determine if a model can be unretired.
     *
     * @return bool
     */
    public function canBeUnretired();
}
