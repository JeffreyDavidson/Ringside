<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Suspension;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

trait HasSuspensions
{
    /**
     * Get the suspensions of the model.
     */
    public function suspensions(): MorphMany
    {
        return $this->morphMany(Suspension::class, 'suspendable');
    }

    /**
     * Get the current suspension of the model.
     */
    public function currentSuspension(): MorphOne
    {
        return $this->morphOne(Suspension::class, 'suspendable')
            ->whereNull('ended_at')
            ->limit(1);
    }

    /**
     * Get the current suspension of the model.
     */
    public function previousSuspensions(): MorphMany
    {
        return $this->suspensions()
            ->whereNotNull('ended_at');
    }

    /**
     * Get the previous suspension of the model.
     */
    public function previousSuspension(): MorphOne
    {
        return $this->morphOne(Suspension::class, 'suspendable')
            ->latest('ended_at')
            ->limit(1);
    }

    /**
     * Check to see if the model has been suspended.
     */
    public function isSuspended(): bool
    {
        return $this->currentSuspension()->exists();
    }

    /**
     * Check to see if the model has been suspended.
     */
    public function hasSuspensions(): bool
    {
        return $this->suspensions()->count() > 0;
    }
}
