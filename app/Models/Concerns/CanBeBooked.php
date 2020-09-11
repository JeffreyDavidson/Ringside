<?php

namespace App\Models\Concerns;

trait CanBeBooked
{
    /**
     * Scope a query to only include bookable managers.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @param  \Illuminate\Database\Eloquent\Builder
     */
    public function scopeBookable($query)
    {
        return $query->where('status', 'bookable');
    }

    /**
     * @return bool
     */
    public function isBookable()
    {
        if ($this->isUnemployed() || $this->isSuspended() || $this->isInjured() || $this->isRetired()) {
            return false;
        }

        return true;
    }
}
