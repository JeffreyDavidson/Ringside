<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Contracts\Bookable;
use App\Models\Matches\EventMatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use LogicException;

/**
 * Provides match-related relationship and status logic for models that can participate
 * in event matches (e.g., Wrestlers, Tag Teams).
 *
 * This trait assumes the consuming model implements the Bookable interface
 * and provides concrete implementations of match-related methods.
 *
 * @template TMatchCompetitor of Model
 *
 * @phpstan-require-implements Bookable<TMatchCompetitor>
 *
 * @example
 * ```php
 * class Wrestler extends Model implements Bookable
 * {
 *     use HasMatches;
 * }
 *
 * $wrestler = Wrestler::find(1);
 * $allMatches = $wrestler->matches;
 * $pastMatches = $wrestler->previousMatches;
 * ```
 */
trait HasMatches
{
    /**
     * Retrieve all event matches this model has participated in.
     *
     * This method provides the polymorphic many-to-many relationship
     * to EventMatch through the EventMatchCompetitor pivot model.
     *
     * @return MorphToMany<EventMatch, Model>
     *                                        A relationship instance for accessing all matches
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $allMatches = $wrestler->matches;
     * $matchCount = $wrestler->matches()->count();
     * ```
     */
    public function matches(): MorphToMany
    {
        /** @var MorphToMany<EventMatch, Model> $relation */
        $relation = $this->morphToMany(EventMatch::class, 'competitor', 'event_match_competitors');

        return $relation;
    }

    /**
     * Retrieve matches that have already occurred (past event date).
     *
     * This filters matches where the associated event's date is before today.
     * Uses a join with the events table to filter by event date.
     *
     * @return MorphToMany<EventMatch, Model>
     *                                        A relationship instance for accessing previous matches
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $pastMatches = $wrestler->previousMatches;
     * $recentMatches = $wrestler->previousMatches()->orderBy('events.date', 'desc')->get();
     * ```
     */
    public function previousMatches(): MorphToMany
    {
        /** @var MorphToMany<EventMatch, Model> $relation */
        $relation = $this->morphToMany(EventMatch::class, 'competitor', 'event_match_competitors')
            ->join('events', 'event_matches.event_id', '=', 'events.id')
            ->where('events.date', '<', today());

        return $relation;
    }

    /**
     * Check if the model can be booked for matches.
     *
     * This method checks the model's bookability status. The actual logic
     * should be implemented in the model itself, typically checking various
     * status conditions like employment, injuries, suspensions, etc.
     *
     * @return bool True if the model can be booked, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->canBeBooked()) {
     *     echo "Wrestler is available for booking";
     * }
     * ```
     */
    public function canBeBooked(): bool
    {
        if (! $this instanceof Bookable) {
            throw new LogicException(static::class.' must implement Bookable to use HasMatches trait');
        }

        // Delegate to the model's isBookable method
        return $this->isBookable();
    }

    /**
     * Check if the model cannot be booked for matches.
     *
     * Convenience method that returns the opposite of canBeBooked().
     *
     * @return bool True if the model cannot be booked, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->cannotBeBooked()) {
     *     echo "Wrestler is not available for booking";
     * }
     * ```
     */
    public function cannotBeBooked(): bool
    {
        return ! $this->canBeBooked();
    }

    /**
     * Get the polymorphic relationship to EventMatch.
     *
     * This method provides access to the underlying polymorphic relationship
     * and can be used for more complex queries or relationship manipulation.
     *
     * @return MorphToMany<EventMatch, Model>
     *                                        The base polymorphic relationship
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * // More complex query using the base relationship
     * $recentMatches = $wrestler->morphMatches()
     *     ->wherePivot('created_at', '>', now()->subMonths(3))
     *     ->get();
     * ```
     */
    protected function morphMatches(): MorphToMany
    {
        return $this->matches();
    }
}
