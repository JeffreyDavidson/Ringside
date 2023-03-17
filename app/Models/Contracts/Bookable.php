<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

interface Bookable
{
    /**
     * Retrieve the event matches participated by the model.
     */
    public function eventMatches(): MorphToMany;

    /**
     * Check to see if the model is bookable.
     */
    public function isBookable(): bool;
}
