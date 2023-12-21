<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Retirement;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface Retirable extends Identifiable
{
    /**
     * Get the retirements of the model.
     *
     * @return MorphMany<Retirement>
     */
    public function retirements(): MorphMany;

    /**
     * Get the current retirement of the model.
     *
     * @return MorphOne<Retirement>
     */
    public function currentRetirement(): MorphOne;

    /**
     * Get the previous retirements of the model.
     *
     * @return MorphMany<Retirement>
     */
    public function previousRetirements(): MorphMany;

    /**
     * Get the previous retirement of the model.
     *
     * @return MorphOne<Retirement>
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
