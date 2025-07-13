<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Matches\EventMatch;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Provides event match relationships for models that can have event matches.
 *
 * This trait provides methods for accessing event matches associated with
 * a model. It's designed to be used by models that have a direct relationship
 * with event matches, such as Event models.
 *
 * @example
 * ```php
 * class Event extends Model
 * {
 *     use HasEventMatches;
 * }
 *
 * $event = Event::find(1);
 * $allMatches = $event->matches;
 * $matchCount = $event->matches()->count();
 * ```
 */
trait HasEventMatches
{
    /**
     * Get all event matches associated with this model.
     *
     * Returns all event matches regardless of their status or outcome.
     *
     * @return HasMany<EventMatch, $this>
     *                                    A relationship instance for accessing all matches
     *
     * @example
     * ```php
     * $event = Event::find(1);
     * $allMatches = $event->matches;
     * $matchCount = $event->matches()->count();
     * ```
     */
    public function matches(): HasMany
    {
        return $this->hasMany(EventMatch::class);
    }

    /**
     * Get event matches for a specific model that has matches.
     *
     * This method is designed to be overridden by models that need to specify
     * a different foreign key or table name for the matches relationship.
     *
     * @param  string  $foreignKey  The foreign key to use for the relationship
     * @return HasMany<EventMatch, $this>
     *                                    A relationship instance for accessing matches
     */
    protected function getMatchesRelation(?string $foreignKey = null): HasMany
    {
        $key = $foreignKey ?? $this->getForeignKey();

        return $this->hasMany(EventMatch::class, $key);
    }
}
