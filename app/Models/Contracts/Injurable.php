<?php

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface Injurable
{
    /**
     * Get the injuries of the model.
     */
    public function injuries(): MorphMany;

    /**
     * Get the current injury of the model.
     */
    public function currentInjury(): MorphOne;

    /**
     * Get the previous injuries of the model.
     */
    public function previousInjuries(): MorphMany;

    /**
     * Get the previous injury of the model.
     */
    public function previousInjury(): MorphOne;

    /**
     * Check to see if the model is injured.
     */
    public function isInjured(): bool;

    /**
     * Check to see if the model has been injured.
     */
    public function hasInjuries(): bool;
}
