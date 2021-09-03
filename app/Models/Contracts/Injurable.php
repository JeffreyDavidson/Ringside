<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Injurable
{
    /**
     * Get all of the injuries of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function injuries();

    /**
     * Get the current injury of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentInjury();

    /**
     * Get the previous injuries of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousInjuries();

    /**
     * Get the previous injury of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousInjury();

    /**
     * Scope a query to include injured models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInjured(Builder $query);

    /**
     * Scope a query to include the current injured at date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCurrentInjuredAtDate(Builder $query);

    /**
     * Scope a query to order by the current injured at date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCurrentInjuredAtDate(Builder $query);

    /**
     * Check to see if the model is injured.
     *
     * @return bool
     */
    public function isInjured();

    /**
     * Check to see if the model has been injured.
     *
     * @return bool
     */
    public function hasInjuries();

    /**
     * Check to see if the model can be injured.
     *
     * @return bool
     */
    public function canBeInjured();

    /**
     * Check to see if the model can be cleared from an injury.
     *
     * @return bool
     */
    public function canBeClearedFromInjury();
}
