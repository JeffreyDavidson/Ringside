<?php

declare(strict_types=1);

namespace App\Models\Validation\Strategies;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Contracts\RetirementValidationStrategy;
use Illuminate\Database\Eloquent\Model;

/**
 * Retirement validation strategy for individual entities.
 *
 * This strategy handles retirement validation for individual entities like
 * Wrestlers, Managers, and Referees that don't have complex relationships
 * requiring additional validation.
 *
 * @example
 * ```php
 * $strategy = new IndividualRetirementValidation();
 * $strategy->validate($wrestler);
 * ```
 */
class IndividualRetirementValidation implements RetirementValidationStrategy
{
    /**
     * Validate that an individual entity can be retired.
     *
     * Performs standard retirement validation checks:
     * - Must not be unemployed
     * - Must not have future employment scheduled
     * - Must not already be retired
     *
     * Note: Released entities CAN be retired - this is a valid business workflow
     * where an entity is first released from employment, then later retired.
     *
     * @param  Model  $entity  The individual entity to validate
     *
     * @throws CannotBeRetiredException When retirement is not allowed
     */
    public function validate(Model $entity): void
    {
        if ($this->isUnemployed($entity)) {
            throw CannotBeRetiredException::unemployed();
        }

        // Note: Released entities CAN be retired - removing this restriction
        // if ($this->isReleased($entity)) {
        //     throw CannotBeRetiredException::released();
        // }

        if (method_exists($entity, 'hasFutureEmployment') && $entity->hasFutureEmployment()) {
            throw CannotBeRetiredException::hasFutureEmployment();
        }

        if (method_exists($entity, 'isRetired') && $entity->isRetired()) {
            throw CannotBeRetiredException::retired();
        }
    }

    /**
     * Check if the entity is unemployed.
     *
     * @param  Model  $entity  The entity to check
     * @return bool True if unemployed, false otherwise
     */
    private function isUnemployed(Model $entity): bool
    {
        return method_exists($entity, 'hasStatus') ? $entity->hasStatus(EmploymentStatus::Unemployed) : false;
    }

    /**
     * Check if the entity is released.
     *
     * @param  Model  $entity  The entity to check
     * @return bool True if released, false otherwise
     */
    private function isReleased(Model $entity): bool
    {
        return method_exists($entity, 'hasStatus') ? $entity->hasStatus(EmploymentStatus::Released) : false;
    }
}
