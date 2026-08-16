<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Builders\Matches\EventMatchBuilder;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Provides polymorphic match-participation relationships for competitors.
 */
trait HasMatchParticipations
{
    /**
     * Get all matches this competitor has participated in.
     *
     * @return MorphToMany<EventMatch, $this, MatchCompetitor>
     */
    public function matches(): MorphToMany
    {
        return $this->morphToMany(
            EventMatch::class,
            'competitor',
            (new MatchCompetitor())->getTable(),
            'competitor_id',
            'match_id',
        )
            ->using(MatchCompetitor::class);
    }

    /**
     * Get previous matches this competitor has participated in.
     *
     * @return MorphToMany<EventMatch, $this, MatchCompetitor>
     */
    public function previousMatches(): MorphToMany
    {
        $relation = $this->matches();
        EventMatchBuilder::constrainToPastEvents($relation->getQuery());

        return $relation;
    }
}
