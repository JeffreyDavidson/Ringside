<?php

declare(strict_types=1);

namespace App\Models\Validation\Strategies;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\CannotBeRetiredException;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Wrestlers\Wrestler;

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
class IndividualRetirementValidation
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
     * @throws CannotBeRetiredException When retirement is not allowed
     */
    public function validate(Wrestler|Manager|Referee $entity): void
    {
        if ($this->isUnemployed($entity)) {
            throw CannotBeRetiredException::unemployed($entity);
        }

        if ($entity->hasFutureEmployment()) {
            throw CannotBeRetiredException::hasFutureEmployment($entity);
        }

        if ($entity->isRetired()) {
            throw CannotBeRetiredException::alreadyRetired($entity);
        }
    }

    /**
     * Check if the entity is unemployed.
     */
    private function isUnemployed(Wrestler|Manager|Referee $entity): bool
    {
        return $entity->hasStatus(EmploymentStatus::Unemployed);
    }
}
