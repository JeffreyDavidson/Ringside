<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Matches\EventMatch;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Provides match relationships for officials.
 */
trait OfficiatesMatches
{
    /**
     * Get all matches this official has officiated.
     *
     * @return BelongsToMany<EventMatch, $this>
     */
    public function matches(): BelongsToMany
    {
        return $this->belongsToMany(EventMatch::class, 'events_matches_referees');
    }

    /**
     * Get previous matches this official has officiated.
     *
     * @return BelongsToMany<EventMatch, $this>
     */
    public function previousMatches(): BelongsToMany
    {
        return $this->matches()->whereHas('event', function ($query) {
            $query->where('date', '<', now());
        });
    }
}
