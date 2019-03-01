<?php

namespace App\Traits;

use App\Models\Suspension;

trait Suspendable
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
     * Get the previous suspension of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousSuspension()
    {
        return $this->morphOne(Suspension::class, 'suspendable')->whereNotNull('ended_at')->latest('started_at');
    }

    /**
     * Suspend a model.
     *
     * @return void
     */
    public function suspend()
    {
        $this->suspensions()->create(['started_at' => today()]);
    }

    /**
     * Check to see if the model is suspended.
     *
     * @return bool
     */
    public function isSuspended()
    {
        return $this->suspensions()->whereNull('ended_at')->exists();
    }

    /**
     * Scope a query to only include suspended models.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSuspended($query)
    {
        return $query->whereHas('suspensions', function ($query) {
            $query->whereNull('ended_at');
        });
    }

    /**
     * Reinstate the suspended model.
     *
     * @return void
     */
    public function reinstate()
    {
        $this->suspension()->update(['ended_at' => today()]);
    }
}
