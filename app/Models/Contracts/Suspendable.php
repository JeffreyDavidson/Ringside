<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Suspendable
{
    /**
     * Get the suspensions of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function suspensions();

    /**
     * Get the current suspension of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentSuspension();

    /**
     * Get the previous suspensions of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousSuspensions();

    /**
     * Get the previous suspension of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousSuspension();

    /**
     * Scope a query to include suspended models.
     *
     * @param   \Illuminate\Database\Eloquent\Builder  $query
     * @return  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuspended(Builder $query);

    /**
     * Scope a query to include current suspension date.
     *
     * @param   \Illuminate\Database\Eloquent\Builder  $query
     * @return  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCurrentSuspendedAtDate(Builder $query);

    /**
     * Scope a query to order by the model's current suspension date.
     *
     * @param   \Illuminate\Database\Eloquent\Builder  $query
     * @return  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCurrentSuspendedAtDate(Builder $query);

    /**
     * Check to see if the model has been suspended.
     *
     * @return bool
     */
    public function isSuspended();

    /**
     * Check to see if the model has been suspended.
     *
     * @return bool
     */
    public function hasSuspensions();

    /**
     * Determine if the model can be suspended.
     *
     * @return bool
     */
    public function canBeSuspended();

    /**
     * Determine if the model can be reinstated.
     *
     * @return bool
     */
    public function canBeReinstated();
}
