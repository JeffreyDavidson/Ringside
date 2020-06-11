<?php

namespace App\Models\Concerns;

use App\Traits\HasCachedAttributes;

trait CanBeCompeted
{
    /**
     *
     */
    public static function bootCanBeCompeted()
    {
        if (config('app.debug')) {
            $traits = class_uses_recursive(static::class);

            if (!in_array(HasCachedAttributes::class, $traits)) {
                throw new \LogicException('CanBeCompeted trait used without HasCachedAttributes trait');
            }
        }
    }

    /**
     * Determine if a manager is bookable.
     *
     * @return bool
     */
    public function getIsCompetableCachedAttribute()
    {
        return $this->status === 'active';
    }

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
