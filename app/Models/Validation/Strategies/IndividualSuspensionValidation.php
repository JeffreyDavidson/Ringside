<?php

declare(strict_types=1);

namespace App\Models\Validation\Strategies;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Roster\CannotBeSuspendedException;
use App\Models\Managers\Manager;
use App\Models\Referees\Referee;
use App\Models\Wrestlers\Wrestler;

/**
 * Suspension validation strategy for individual entities.
 *
 * This strategy handles suspension validation for individual entities like
 * Wrestlers, Managers, and Referees.
 */
class IndividualSuspensionValidation
{
    /**
     * Validate that an individual entity can be suspended.
     *
     * @throws CannotBeSuspendedException When suspension is not allowed
     */
    public function validate(Wrestler|Manager|Referee $entity): void
    {
        if ($this->isUnemployed($entity)) {
            throw CannotBeSuspendedException::unemployed($entity);
        }

        if ($this->isReleased($entity)) {
            throw CannotBeSuspendedException::released($entity);
        }

        if ($entity->isRetired()) {
            throw CannotBeSuspendedException::retired($entity);
        }

        if ($entity->hasFutureEmployment()) {
            throw CannotBeSuspendedException::hasFutureEmployment($entity);
        }

        if ($entity->isInjured()) {
            throw CannotBeSuspendedException::injured($entity);
        }

        if ($entity->isSuspended()) {
            throw CannotBeSuspendedException::suspended($entity);
        }

    }

    /**
     * Check if the entity is unemployed.
     */
    private function isUnemployed(Wrestler|Manager|Referee $entity): bool
    {
        return $entity->hasStatus(EmploymentStatus::Unemployed);
    }

    /**
     * Check if the entity is released.
     */
    private function isReleased(Wrestler|Manager|Referee $entity): bool
    {
        return $entity->hasStatus(EmploymentStatus::Released);
    }
}
