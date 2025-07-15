<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Matches\EventMatch;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Provides match relationships for models that can be booked as competitors.
 *
 * This trait implements the Bookable interface for competitor models
 * that participate in matches through the event_match_competitors pivot table.
 * It's designed to be used by models like Wrestler and TagTeam.
 */
trait IsBookableCompetitor
{
    /**
     * Get all matches this competitor has participated in.
     */
    public function matches(): MorphToMany
    {
        return $this->morphToMany(EventMatch::class, 'competitor', 'event_match_competitors');
    }

    /**
     * Get previous matches this competitor has participated in.
     */
    public function previousMatches(): MorphToMany
    {
        return $this->matches()->whereHas('event', function ($query) {
            $query->where('date', '<', now());
        });
    }
}