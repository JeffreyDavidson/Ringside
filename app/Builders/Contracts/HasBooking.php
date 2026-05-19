<?php

declare(strict_types=1);

namespace App\Builders\Contracts;

use Carbon\Carbon;

/**
 * Contract for query builders that provide booking availability checking.
 *
 * This interface defines the standard methods for checking booking availability
 * across different types of builders in the wrestling promotion system.
 * Only entities that can be booked for matches (Wrestlers, TagTeams) should
 * implement this interface.
 *
 * BUSINESS CONTEXT:
 * Booking availability requires checking:
 * - Entity is generally available (employed, not injured, not suspended, not retired)
 * - Entity is not already booked for matches on the target date
 * - Entity meets any specific booking requirements (e.g., tag teams need minimum wrestlers)
 *
 * DESIGN PATTERN:
 * Strategy pattern - Each builder implements its own booking strategy
 * while conforming to the same interface contract.
 *
 * @example
 * ```php
 * function getBookableEntities(HasBooking $builder): Collection
 * {
 *     return $builder->bookable()->get();
 * }
 *
 * // Works polymorphically with any builder implementing this interface
 * $bookableWrestlers = getBookableEntities(Wrestler::query());
 * $bookableTagTeams = getBookableEntities(TagTeam::query());
 * ```
 */
interface HasBooking
{
    /**
     * Scope a query to include bookable entities.
     *
     * Filters entities that are currently available for booking in matches.
     * The specific criteria for booking availability varies by entity type
     * but must include general availability checks.
     *
     * @return static The builder instance for method chaining
     */
    public function bookable(): static;

    /**
     * Scope a query to include entities available on a specific date.
     *
     * Filters entities that are available for booking on the given date,
     * considering both their general availability status and any existing
     * match bookings on that date.
     *
     * @param  Carbon  $date  The date to check availability for
     * @return static The builder instance for method chaining
     */
    public function availableOn(Carbon $date): static;

    /**
     * Scope a query to exclude entities already booked on a specific date.
     *
     * Filters out entities that have existing match bookings on the given
     * date to prevent double-booking conflicts.
     *
     * @param  Carbon  $date  The date to check for existing bookings
     * @return static The builder instance for method chaining
     */
    public function notBookedOn(Carbon $date): static;
}
