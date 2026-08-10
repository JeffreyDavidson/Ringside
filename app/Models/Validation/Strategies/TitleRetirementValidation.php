<?php

declare(strict_types=1);

namespace App\Models\Validation\Strategies;

use App\Exceptions\Titles\CannotBeRetiredException;
use App\Models\Titles\Title;

/**
 * Retirement validation strategy for Title entities.
 *
 * This strategy handles retirement validation for Title entities that have
 * specific business rules around championship retirement.
 *
 * @example
 * ```php
 * $strategy = new TitleRetirementValidation();
 * $strategy->validate($title);
 * ```
 */
class TitleRetirementValidation
{
    /**
     * Validate that a Title can be retired.
     *
     * Performs Title-specific retirement validation checks:
     * - Must not already be retired
     * - Must not be unactivated (titles that haven't debuted can't be retired)
     * - Must not have future activation scheduled
     *
     * @throws CannotBeRetiredException When retirement is not allowed
     */
    public function validate(Title $entity): void
    {
        if ($entity->isRetired()) {
            throw CannotBeRetiredException::alreadyRetired($entity);
        }

        if (! $entity->hasActivityPeriods()) {
            throw CannotBeRetiredException::unactivated($entity);
        }

        if ($entity->hasFutureDebut()) {
            throw CannotBeRetiredException::hasFutureDebut($entity);
        }
    }
}
