<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface Suspendable
{
    /**
     * Get the suspensions of the model.
     */
    public function suspensions(): MorphMany;

    /**
     * Get the current suspension of the model.
     */
    public function currentSuspension(): MorphOne;

    /**
     * Get the previous suspensions of the model.
     */
    public function previousSuspensions(): MorphMany;

    /**
     * Get the previous suspension of the model.
     */
    public function previousSuspension(): MorphOne;

    /**
     * Determine if the model is suspended.
     */
    public function isSuspended(): bool;

    /**
     * Check to see if the model has been suspended.
     */
    public function hasSuspensions(): bool;
}
