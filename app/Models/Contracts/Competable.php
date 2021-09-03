<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Builder;

interface Competable
{
    /**
     * Scope a query to include competable models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder  $query
     */
    public function scopeCompetable(Builder $query);

    /**
     * Check to see if the model can be competed for.
     *
     * @return bool
     */
    public function isCompetable();
}
