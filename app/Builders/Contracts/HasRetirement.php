<?php

declare(strict_types=1);

namespace App\Builders\Contracts;

/**
 * Contract for query builders that provide retirement status checking.
 *
 * This interface defines the standard methods for checking retirement status
 * across different types of builders in the wrestling promotion system.
 * Each implementation can define its own business logic for what constitutes
 * retirement while maintaining a consistent API.
 *
 * BUSINESS CONTEXT:
 * Different entity types have different retirement requirements:
 * - Individual roster members: entity has active retirement record
 * - Tag teams: team retired as unit (independent of individual wrestler retirement)
 * - Future entity types may have their own specific retirement logic
 *
 * DESIGN PATTERN:
 * Strategy pattern - Each builder implements its own retirement strategy
 * while conforming to the same interface contract.
 *
 * @example
 * ```php
 * function getRetiredEntities(HasRetirement $builder): Collection
 * {
 *     return $builder->retired()->get();
 * }
 *
 * // Works polymorphically with any builder implementing this interface
 * $retiredWrestlers = getRetiredEntities(Wrestler::query());
 * $retiredTagTeams = getRetiredEntities(TagTeam::query());
 * ```
 */
interface HasRetirement
{
    /**
     * Scope a query to include retired entities.
     *
     * Filters entities that are currently retired and no longer active.
     * The specific criteria for retirement varies by entity type.
     *
     * @return static The builder instance for method chaining
     */
    public function retired(): static;
}
