<?php

declare(strict_types=1);

namespace App\Builders\Contracts;

/**
 * Contract for query builders that provide employment status checking.
 *
 * This interface defines the standard methods for checking employment status
 * across different types of builders in the wrestling promotion system.
 * Each implementation can define its own business logic for what constitutes
 * employment while maintaining a consistent API.
 *
 * BUSINESS CONTEXT:
 * Different entity types have different employment requirements:
 * - Individual roster members: entity has active employment contract
 * - Composite entities (tag teams): entity has contract AND all members have contracts
 * - Future entity types may have their own specific employment logic
 *
 * DESIGN PATTERN:
 * Strategy pattern - Each builder implements its own employment strategy
 * while conforming to the same interface contract.
 *
 * @example
 * ```php
 * function getEmployedEntities(HasEmployment $builder): Collection
 * {
 *     return $builder->employed()->get();
 * }
 *
 * // Works polymorphically with any builder implementing this interface
 * $employedWrestlers = getEmployedEntities(Wrestler::query());
 * $employedTagTeams = getEmployedEntities(TagTeam::query());
 * ```
 */
interface HasEmployment
{
    /**
     * Scope a query to include unemployed entities.
     *
     * Filters entities that are currently unemployed and not under contract.
     * The specific criteria for unemployment varies by entity type.
     *
     * @return static The builder instance for method chaining
     */
    public function unemployed(): static;

    /**
     * Scope a query to include employed entities.
     *
     * Filters entities that are currently employed and under contract.
     * The specific criteria for employment varies by entity type.
     *
     * @return static The builder instance for method chaining
     */
    public function employed(): static;

    /**
     * Scope a query to include released entities.
     *
     * Filters entities that have been released from their contracts.
     * The specific criteria for released status varies by entity type.
     *
     * @return static The builder instance for method chaining
     */
    public function released(): static;

    /**
     * Scope a query to include entities with future employment.
     *
     * Filters entities that have been signed but their employment
     * hasn't started yet. The specific criteria varies by entity type.
     *
     * @return static The builder instance for method chaining
     */
    public function futureEmployed(): static;
}
