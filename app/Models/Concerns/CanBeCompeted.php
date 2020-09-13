<?php

namespace App\Models\Concerns;

trait CanBeCompeted
{
    /**
     * Scope a query to only include bookable managers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompetable($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Check to see if the model can be competed for.
     *
     * @return bool
     */
    public function isCompetable()
    {
        return !is_null($this->activated_at) && $this->activated_at->isPast();
    }
}
