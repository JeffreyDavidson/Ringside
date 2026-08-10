<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Provides polymorphic match relationships for competitors.
 */
trait IsBookableCompetitor
{
    /**
     * Get all matches this competitor has participated in.
     *
     * @return MorphToMany<EventMatch, $this, MatchCompetitor>
     */
    public function matches(): MorphToMany
    {
        return $this->morphToMany(EventMatch::class, 'competitor', 'event_match_competitors')
            ->using(MatchCompetitor::class);
    }

    /**
     * Get previous matches this competitor has participated in.
     *
     * @return MorphToMany<EventMatch, $this, MatchCompetitor>
     */
    public function previousMatches(): MorphToMany
    {
        return $this->matches()->whereHas('event', function ($query) {
            $query->where('date', '<', now());
        });
    }
}
