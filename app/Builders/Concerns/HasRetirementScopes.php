<?php

declare(strict_types=1);

namespace App\Builders\Concerns;

/**
 * Trait for builders that need retirement-related query scopes.
 *
 * Provides query methods for filtering entities by their retirement status.
 * Applies to all entities as all can be retired according to business rules.
 */
trait HasRetirementScopes
{
    /**
     * Scope a query to include retired entities.
     *
     * Filters entities that have officially retired and are no longer
     * active. Uses the currentRetirement relationship to check for
     * active retirements (where ended_at is null). Retired entities
     * cannot participate unless they come out of retirement.
     *
     * @return static The builder instance for method chaining
     */
    public function retired(): static
    {
        $this->whereHas('currentRetirement');

        return $this;
    }
}
