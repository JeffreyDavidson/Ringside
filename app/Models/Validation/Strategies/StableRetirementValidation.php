<?php

declare(strict_types=1);

namespace App\Models\Validation\Strategies;

use App\Exceptions\Roster\CannotBeRetiredException;
use App\Models\Stables\Stable;

/**
 * Retirement validation strategy for stable entities.
 *
 * This strategy handles retirement validation for stables that have complex
 * activation periods and member relationships.
 */
class StableRetirementValidation
{
    /**
     * Validate that a stable can be retired.
     *
     * Performs stable-specific retirement validation checks:
     * - Must not be unactivated (never been active)
     * - Must not have future activation scheduled
     * - Must not already be retired
     *
     * @throws CannotBeRetiredException When retirement is not allowed
     */
    public function validate(Stable $stable): void
    {
        if ($this->isUnactivated($stable)) {
            throw CannotBeRetiredException::unactivated($stable);
        }

        if ($stable->hasFutureEstablishment()) {
            throw CannotBeRetiredException::hasFutureEstablishment($stable);
        }

        if ($stable->isRetired()) {
            throw CannotBeRetiredException::alreadyRetired($stable);
        }
    }

    /**
     * Check if the stable is unactivated (never been active).
     */
    private function isUnactivated(Stable $stable): bool
    {
        return ! $stable->hasActivityPeriods();
    }
}
