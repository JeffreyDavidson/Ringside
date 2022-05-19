<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Suspension;

trait HasSuspensions
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
    public function currentSuspension()
    {
        return $this->morphOne(Suspension::class, 'suspendable')
            ->whereNull('ended_at')
            ->limit(1);
    }

    /**
     * Get the current suspension of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousSuspensions()
    {
        return $this->suspensions()
            ->whereNotNull('ended_at');
    }

    /**
     * Get the previous suspension of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousSuspension()
    {
        return $this->morphOne(Suspension::class, 'suspendable')
            ->latest('ended_at')
            ->limit(1);
    }

    /**
     * Check to see if the model has been suspended.
     *
     * @return bool
     */
    public function isSuspended()
    {
        return $this->currentSuspension()->exists();
    }

    /**
     * Check to see if the model has been suspended.
     *
     * @return bool
     */
    public function hasSuspensions()
    {
        return $this->suspensions()->count() > 0;
    }
}
