<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Injury;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

interface Injurable extends Identifiable
{
    /**
     * Get the injuries of the model.
     *
     * @return MorphMany<Injury>
     */
    public function injuries(): MorphMany;

    /**
     * Get the current injury of the model.
     *
     * @return MorphOne<Injury>
     */
    public function currentInjury(): MorphOne;

    /**
     * Get the previous injuries of the model.
     *
     * @return MorphMany<Injury>
     */
    public function previousInjuries(): MorphMany;

    /**
     * Get the previous injury of the model.
     *
     * @return MorphOne<Injury>
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
