<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Releasable
{
    /**
     * Scope a query to include released models.
     *
     * @param   \Illuminate\Database\Eloquent\Builder  $query
     * @return  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeReleased(Builder $query);

    /**
     * Scope a query to include model's released at date.
     *
     * @param   \Illuminate\Database\Eloquent\Builder  $query
     * @return  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithReleasedAtDate(Builder $query);

    /**
     * Scope a query to order models by current released at date.
     *
     * @param   \Illuminate\Database\Eloquent\Builder  $query
     * @return  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrderByCurrentReleasedAtDate(Builder $query);

    /**
     * Check to see if the model has been released.
     *
     * @return bool
     */
    public function isReleased();

    /**
     * Determine if a model can be released.
     *
     * @return bool
     */
    public function canBeReleased();
}
