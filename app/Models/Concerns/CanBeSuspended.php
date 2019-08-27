<?php

namespace App\Models\Concerns;

use App\Models\Suspension;

trait CanBeSuspended
{
    /**
     * Get the suspensions of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function suspensions()
    {
        return $this->morphMany(Suspension::class, 'suspendable');
    }

    /**
     * Get the current suspension of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function suspension()
    {
        return $this->morphOne(Suspension::class, 'suspendable')->whereNull('ended_at');
    }

    /**
     * Determine if a model is suspended.
     *
     * @return bool
     */
    public function getIsSuspendedAttribute()
    {
        return $this->status === 'suspended';
    }

    /**
     * Scope a query to only include suspended models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    /**
     * Suspend a model.
     *
     * @return \App\Models\Suspension
     */
    public function suspend()
    {
        $this->suspensions()->create(['started_at' => now()]);
    }

    /**
     * Reinstate a model.
     *
     * @return bool
     */
    public function reinstate()
    {
        $this->suspension()->update(['ended_at' => now()]);
    }
}
