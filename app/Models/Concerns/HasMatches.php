<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\EventMatch;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait HasMatches
{
    /**
     * Retrieve the event matches participated by the model.
     *
     * @return MorphToMany<EventMatch, $this>
     */
    public function previousMatches(): MorphToMany
    {
        return $this->matches()
            ->join('events', 'event_matches.event_id', '=', 'events.id')
            ->where('events.date', '<', today());
    }

    /**
     * Check to see if the model is bookable.
     */
    public function isBookable(): bool
    {
        if ($this->isNotInEmployment() || $this->isSuspended() || $this->isInjured() || $this->hasFutureEmployment()) {
            return false;
        }

        return true;
    }
}
