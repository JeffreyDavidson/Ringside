<?php

declare(strict_types=1);

namespace App\Models\Validation\Strategies;

use App\Enums\Shared\EmploymentStatus;
use App\Exceptions\Status\CannotBeSuspendedException;
use App\Models\Contracts\SuspensionValidationStrategy;
use Illuminate\Database\Eloquent\Model;

/**
 * Suspension validation strategy for individual entities.
 *
 * This strategy handles suspension validation for individual entities like
 * Wrestlers, Managers, and Referees.
 */
class IndividualSuspensionValidation implements SuspensionValidationStrategy
{
    /**
     * Validate that an individual entity can be suspended.
     *
     * @param  Model  $entity  The individual entity to validate
     *
     * @throws CannotBeSuspendedException When suspension is not allowed
     */
    public function validate(Model $entity): void
    {
        if ($this->isUnemployed($entity)) {
            throw CannotBeSuspendedException::unemployed();
        }

        if ($this->isReleased($entity)) {
            throw CannotBeSuspendedException::released();
        }

        if (method_exists($entity, 'isRetired') && $entity->isRetired()) {
            throw CannotBeSuspendedException::retired();
        }

        if (method_exists($entity, 'hasFutureEmployment') && $entity->hasFutureEmployment()) {
            throw CannotBeSuspendedException::hasFutureEmployment();
        }

        if (method_exists($entity, 'isSuspended') && $entity->isSuspended()) {
            throw CannotBeSuspendedException::suspended();
        }

        if (method_exists($entity, 'isInjured') && $entity->isInjured()) {
            throw CannotBeSuspendedException::injured();
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
