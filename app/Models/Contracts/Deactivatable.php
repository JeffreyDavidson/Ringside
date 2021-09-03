<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Deactivatable
{
    /**
     * Check to see if the model is currently deactivated.
     *
     * @return bool
     */
    public function isDeactivated();

    /**
     * Scope a query to include deactivated models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeDeactivated(Builder $query);

    /**
     * Scope a query to include the last deactivation date for models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeWithLastDeactivationDate(Builder $query);

    /**
     * Scope a query to order models by the model's last deactivation date.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeOrderByLastDeactivationDate(Builder $query);
}
