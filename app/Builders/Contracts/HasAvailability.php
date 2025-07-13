<?php

declare(strict_types=1);

namespace App\Builders\Contracts;

/**
 * Contract for query builders that provide availability checking.
 *
 * This interface defines the standard methods for checking entity availability
 * across different types of builders in the wrestling promotion system.
 * Each implementation can define its own business logic for what constitutes
 * "available" vs "unavailable" while maintaining a consistent API.
 *
 * BUSINESS CONTEXT:
 * Different entity types have different availability requirements:
 * - Individual roster members: employed + not injured + not suspended + not retired
 * - Tag teams: employed + not suspended + not retired + has available wrestlers
 * - Future entity types may have their own specific availability logic
 *
 * DESIGN PATTERN:
 * Strategy pattern - Each builder implements its own availability strategy
 * while conforming to the same interface contract.
 *
 * @example
 * ```php
 * function getAvailableEntities(HasAvailability $builder): Collection
 * {
 *     return $builder->available()->get();
 * }
 *
 * // Works polymorphically with any builder implementing this interface
 * $availableWrestlers = getAvailableEntities(Wrestler::query());
 * $availableTagTeams = getAvailableEntities(TagTeam::query());
 * ```
 */
interface HasAvailability
{
    /**
     * Scope a query to include available entities.
     *
     * Filters entities that are currently available to perform their duties.
     * The specific criteria for availability varies by entity type but this
     * method provides a consistent interface for availability checking.
     *
     * @return static The builder instance for method chaining
     */
    public function available(): static;

    /**
     * Scope a query to include unavailable entities.
     *
     * Filters entities that cannot currently perform their duties.
     * This is typically the inverse of the available() scope but
     * implementations may define their own specific logic.
     *
     * @return static The builder instance for method chaining
     */
    public function unavailable(): static;
}
