<?php

namespace App\Models\Concerns;

use App\Models\EventMatch;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasMatches
{
    /**
     * Retrieve the event matches participated by the model.
     */
    public function eventMatches(): MorphToMany
    {
        return $this->morphToMany(EventMatch::class, 'event_match_competitor');
    }
}
