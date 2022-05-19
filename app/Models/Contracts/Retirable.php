<?php

declare(strict_types=1);

namespace App\Models\Contracts;

interface Retirable
{
    /**
     * Get the retirements of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function retirements();

    /**
     * Get the current retirement of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function currentRetirement();

    /**
     * Get the previous retirements of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function previousRetirements();

    /**
     * Get the previous retirement of the model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphOne
     */
    public function previousRetirement();

    /**
     * Determine if the model is retired.
     *
     * @return bool
     */
    public function isRetired();

    /**
     * Check to see if the model has been retired.
     *
     * @return bool
     */
    public function hasRetirements();

    /**
     * Determine if a model can be retired.
     *
     * @return bool
     */
    public function canBeRetired();

    /**
     * Determine if a model can be unretired.
     *
     * @return bool
     */
    public function canBeUnretired();
}
