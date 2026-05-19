<?php

declare(strict_types=1);

namespace App\Builders\Contracts;

/**
 * Contract for query builders that provide suspension status checking.
 *
 * This interface defines the standard methods for checking suspension status
 * across different types of builders in the wrestling promotion system.
 * Each implementation can define its own business logic for what constitutes
 * suspension while maintaining a consistent API.
 *
 * BUSINESS CONTEXT:
 * Different entity types have different suspension requirements:
 * - Individual roster members: entity has active suspension record
 * - Tag teams: team suspension (synchronized with wrestlers via business actions)
 * - Future entity types may have their own specific suspension logic
 *
 * DESIGN PATTERN:
 * Strategy pattern - Each builder implements its own suspension strategy
 * while conforming to the same interface contract.
 *
 * @example
 * ```php
 * function getSuspendedEntities(HasSuspension $builder): Collection
 * {
 *     return $builder->suspended()->get();
 * }
 *
 * // Works polymorphically with any builder implementing this interface
 * $suspendedWrestlers = getSuspendedEntities(Wrestler::query());
 * $suspendedTagTeams = getSuspendedEntities(TagTeam::query());
 * ```
 */
interface HasSuspension
{
    /**
     * Scope a query to include suspended entities.
     *
     * Filters entities that are currently suspended and cannot perform
     * their duties until reinstated. The specific criteria for suspension
     * varies by entity type.
     *
     * @return static The builder instance for method chaining
     */
    public function suspended(): static;
}
