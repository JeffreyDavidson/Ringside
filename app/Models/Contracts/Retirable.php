<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface Retirable
{
    /**
     * Get the retirements of the model.
     */
    public function retirements(): MorphMany;

    /**
     * Get the current retirement of the model.
     */
    public function currentRetirement(): MorphOne;

    /**
     * Get the previous retirements of the model.
     */
    public function previousRetirements(): MorphMany;

    /**
     * Get the previous retirement of the model.
     */
    public function previousRetirement(): MorphOne;

    /**
     * Determine if the model is retired.
     */
    public function isRetired(): bool;

    /**
     * Check to see if the model has been retired.
     */
    public function hasRetirements(): bool;
}
