<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Events\Event;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Provides event-related relationships for models that can hold or be associated with events.
 *
 * This trait provides methods for accessing events and matches, with support for
 * both current and previous events. It's designed to be used by models that
 * have a direct relationship with events or event matches.
 *
 * @example
 * ```php
 * class Venue extends Model
 * {
 *     use HoldsEvents;
 * }
 *
 * $venue = Venue::find(1);
 * $allEvents = $venue->events;
 * $pastEvents = $venue->previousEvents;
 * ```
 */
trait HoldsEvents
{
    /**
     * Get all events associated with this model.
     *
     * Returns all events regardless of their date or status.
     *
     * @return HasMany<Event, $this>
     *                               A relationship instance for accessing all events
     *
     * @example
     * ```php
     * $venue = Venue::find(1);
     * $allEvents = $venue->events;
     * $eventCount = $venue->events()->count();
     * ```
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get previous events (those with dates in the past).
     *
     * Returns events that have already taken place.
     *
     * @return HasMany<Event, $this>
     *                               A relationship instance for accessing previous events
     *
     * @example
     * ```php
     * $venue = Venue::find(1);
     * $pastEvents = $venue->previousEvents;
     * $recentEvents = $venue->previousEvents()->orderBy('date', 'desc')->get();
     * ```
     */
    public function previousEvents(): HasMany
    {
        return $this->events()
            ->where('date', '<', today());
    }

    /**
     * Get future events (those with dates in the future).
     *
     * Returns events that are scheduled to take place.
     *
     * @return HasMany<Event, $this>
     *                               A relationship instance for accessing future events
     *
     * @example
     * ```php
     * $venue = Venue::find(1);
     * $upcomingEvents = $venue->futureEvents;
     * $nextEvent = $venue->futureEvents()->orderBy('date')->first();
     * ```
     */
    public function futureEvents(): HasMany
    {
        return $this->events()
            ->where('date', '>', today());
    }

    /**
     * Get events for a specific model that holds events.
     *
     * This method is designed to be overridden by models that need to specify
     * a different foreign key or table name for the events relationship.
     *
     * @param  string  $foreignKey  The foreign key to use for the relationship
     * @return HasMany<Event, $this>
     *                               A relationship instance for accessing events
     */
    protected function getEventsRelation(?string $foreignKey = null): HasMany
    {
        $key = $foreignKey ?? $this->getForeignKey();

        return $this->hasMany(Event::class, $key);
    }
}
