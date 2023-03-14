<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait CanHaveMatches
{
    /**
     * Retrieve the event matches participated by the model.
     */
    public function eventMatches(): MorphToMany
    {
        return $this->morphToMany(EventMatch::class, 'event_match_competitor');
    }
}
