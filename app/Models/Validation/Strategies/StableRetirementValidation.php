<?php

declare(strict_types=1);

namespace App\Models\Validation\Strategies;

use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Contracts\RetirementValidationStrategy;
use Illuminate\Database\Eloquent\Model;

/**
 * Retirement validation strategy for stable entities.
 *
 * This strategy handles retirement validation for stables that have complex
 * activation periods and member relationships.
 */
class StableRetirementValidation implements RetirementValidationStrategy
{
    /**
     * Validate that a stable can be retired.
     *
     * Performs stable-specific retirement validation checks:
     * - Must not be unactivated (never been active)
     * - Must not have future activation scheduled
     * - Must not already be retired
     *
     * @param  Model  $stable  The stable entity to validate
     *
     * @throws CannotBeRetiredException When retirement is not allowed
     */
    public function validate(Model $stable): void
    {
        if ($this->isUnactivated($stable)) {
            throw CannotBeRetiredException::unemployed(); // Reuse unemployed exception for unactivated
        }

        if (method_exists($stable, 'hasFutureActivity') && $stable->hasFutureActivity()) {
            throw CannotBeRetiredException::hasFutureEmployment(); // Reuse for future activation
        }

        if (method_exists($stable, 'isRetired') && $stable->isRetired()) {
            throw CannotBeRetiredException::retired();
        }
    }

    /**
     * Check if the stable is unactivated (never been active).
     *
     * @param  Model  $stable  The stable to check
     * @return bool True if unactivated, false otherwise
     */
    private function isUnactivated(Model $stable): bool
    {
        return method_exists($stable, 'hasActivityPeriods') && !$stable->hasActivityPeriods();
    }
}
