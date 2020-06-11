<?php

namespace App\Models\Concerns;

use App\Traits\HasCachedAttributes;

trait CanBeBooked
{
    /**
     *
     */
    public static function bootCanBeBooked()
    {
        if (config('app.debug')) {
            $traits = class_uses_recursive(static::class);

            if (!in_array(HasCachedAttributes::class, $traits)) {
                throw new \LogicException('CanBeBooked trait used without HasCachedAttributes trait');
            }
        }
    }

    /**
     * Determine if a manager is bookable.
     *
     * @return bool
     */
    public function getIsBookableCachedAttribute()
    {
        return $this->status === 'bookable';
    }

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
        if ($this->currentEmployment()->doesntExist()) {
            return false;
        }

        if ($this->currentSuspension()->exists()) {
            return false;
        }

        if ($this->currentInjury()->exists()) {
            return false;
        }

        if ($this->currentRetirement()->exists()) {
            return false;
        }

        return true;
    }
}
