<?php

declare(strict_types=1);

namespace App\Builders\Shared;

use App\Models\Shared\Venue;
use Illuminate\Database\Eloquent\Builder;

/**
 * Custom query builder for the Venue model.
 *
 * Provides specialized query methods for filtering venues by their event history
 * and scheduling status. This builder enables easy filtering of venues based on
 * their past events, future bookings, and overall event hosting experience.
 * Useful for venue management, booking decisions, and historical analysis.
 *
 * @template TModel of Venue
 *
 * @extends Builder<TModel>
 *
 * @example
 * ```php
 * // Get venues that have hosted events
 * $experiencedVenues = Venue::query()->withEvents()->get();
 *
 * // Get venues with upcoming events scheduled
 * $bookedVenues = Venue::query()->withFutureEvents()->get();
 *
 * // Find available venues (no events or no future events)
 * $availableVenues = Venue::query()->withoutEvents()->get();
 *
 * // Chain conditions for complex venue searches
 * $activeVenues = Venue::query()
 *     ->withPastEvents()
 *     ->withFutureEvents()
 *     ->get();
 * ```
 */
class VenueBuilder extends Builder
{
    /**
     * Scope a query to include venues that have hosted events.
     *
     * Filters venues that have at least one event associated with them,
     * regardless of when the event occurred. These venues have hosting
     * experience and established event infrastructure.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * $experiencedVenues = Venue::query()->withEvents()->get();
     * ```
     */
    public function withEvents(): static
    {
        $this->whereHas('events');

        return $this;
    }

    /**
     * Scope a query to include venues that have hosted past events.
     *
     * Filters venues that have successfully hosted events in the past.
     * These venues have proven track records and established relationships
     * with event organizers. Useful for finding experienced venues for
     * future bookings.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * $experiencedVenues = Venue::query()->withPastEvents()->get();
     * ```
     */
    public function withPastEvents(): static
    {
        $this->whereHas('events', function (Builder $query) {
            $query->where('date', '<', today());
        });

        return $this;
    }

    /**
     * Scope a query to include venues that have future events scheduled.
     *
     * Filters venues that have confirmed bookings for upcoming events.
     * These venues are already committed and may not be available for
     * additional bookings on those dates. Useful for checking venue
     * availability and scheduling conflicts.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * $bookedVenues = Venue::query()->withFutureEvents()->get();
     * ```
     */
    public function withFutureEvents(): static
    {
        $this->whereHas('events', function (Builder $query) {
            $query->where('date', '>', today());
        });

        return $this;
    }

    /**
     * Scope a query to include venues that haven't hosted any events yet.
     *
     * Filters venues that have no event history. These venues may be new
     * to the system, newly constructed, or haven't been used for events yet.
     * Useful for finding untapped venue opportunities or identifying venues
     * that need promotion to event organizers.
     *
     * @return static The builder instance for method chaining
     *
     * @example
     * ```php
     * $newVenues = Venue::query()->withoutEvents()->get();
     * ```
     */
    public function withoutEvents(): static
    {
        $this->whereDoesntHave('events');

        return $this;
    }
}
