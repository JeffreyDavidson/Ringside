<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

/**
 * Trait for builders that need availability-related query scopes.
 *
 * Provides common query patterns for filtering entities by their availability status.
 * This trait contains shared logic used across multiple builder types to reduce
 * code duplication in availability checking.
 */
trait HasAvailabilityScopes
{
    /**
     * Add a "not retired" condition to the current query.
     *
     * This is a common pattern used across multiple availability methods
     * to ensure entities are not currently retired.
     *
     * @return static The builder instance for method chaining
     */
    protected function whereNotRetired(): static
    {
        $this->whereDoesntHave('currentRetirement');

        return $this;
    }

    /**
     * Add a "not suspended" condition to the current query.
     *
     * This is a common pattern used across multiple availability methods
     * to ensure entities are not currently suspended.
     *
     * @return static The builder instance for method chaining
     */
    protected function whereNotSuspended(): static
    {
        $this->whereDoesntHave('currentSuspension');

        return $this;
    }

    /**
     * Add a "not injured" condition to the current query.
     *
     * This is a common pattern used across availability methods for individual
     * roster members to ensure entities are not currently injured.
     *
     * @return static The builder instance for method chaining
     */
    protected function whereNotInjured(): static
    {
        $this->whereDoesntHave('currentInjury');

        return $this;
    }

    /**
     * Add an "employed" condition to the current query.
     *
     * This is a common pattern used across multiple availability methods
     * to ensure entities have active employment contracts.
     *
     * @return static The builder instance for method chaining
     */
    protected function whereEmployed(): static
    {
        $this->whereHas('currentEmployment', function ($query) {
            $query->where('started_at', '<=', now());
        });

        return $this;
    }

    /**
     * Create a basic unavailability query structure.
     *
     * Provides the common OR conditions for entities that are unavailable:
     * - No current employment
     * - Currently suspended
     * - Currently retired
     *
     * @return static The builder instance for method chaining
     */
    protected function whereBasicUnavailabilityConditions(): static
    {
        $this->where(function ($query) {
            $query->whereDoesntHave('currentEmployment')
                ->orWhereHas('currentSuspension')
                ->orWhereHas('currentRetirement');
        });

        return $this;
    }
}
