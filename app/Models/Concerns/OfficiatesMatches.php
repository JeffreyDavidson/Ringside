<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Matches\EventMatch;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Provides match relationships for models that officiate matches.
 *
 * This trait implements the Bookable interface for models
 * that officiate matches through the events_matches_referees pivot table.
 * It's designed to be used by officials like referees.
 */
trait OfficiatesMatches
{
    /**
     * Get all matches this official has officiated.
     */
    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(EventMatch::class, 'events_matches_referees');
    }

    /**
     * Get previous matches this official has officiated.
     */
    public function previousMatches(): BelongsToMany
    {
        return $this->matches()->whereHas('event', function ($query) {
            $query->where('date', '<', now());
        });
    }
}
