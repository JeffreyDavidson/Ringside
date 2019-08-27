<?php

namespace App\Models\Concerns;

use App\Models\Employment;
use Illuminate\Database\Eloquent\Builder;

trait CanBeEmployed
{
    /**
     * Get all of the employments of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function employments()
    {
        return $this->morphMany(Employment::class, 'employable');
    }

    /**
     * Get the current employment of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function employment()
    {
        return $this->morphOne(Employment::class, 'employable')->whereNull('ended_at');
    }

    /**
     * Determine if a model is employed.
     *
     * @return bool
     */
    public function getIsEmployedAttribute()
    {
        return $this->employments()->where('started_at', '<=', now())->whereNull('ended_at')->exists();
    }

    /**
     * Scope a query to only include pending introduction models.
     * These model have not been employed.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopePendingIntroduction($query)
    {
        return $query->where('status', 'pending-introduction');
    }

    /**
     * Activate a model.
     *
     * @return bool
     */
    public function activate()
    {
        return $this->employments()->updateOrCreate(['started_at' => null], ['started_at' => now()]);
    }
}
