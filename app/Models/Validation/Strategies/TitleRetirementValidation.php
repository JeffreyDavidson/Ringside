<?php

declare(strict_types=1);

namespace App\Models\Validation\Strategies;

use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Contracts\RetirementValidationStrategy;
use App\Models\Titles\Title;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

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
class TitleRetirementValidation implements RetirementValidationStrategy
{
    /**
     * Validate that a Title can be retired.
     *
     * Performs Title-specific retirement validation checks:
     * - Must not already be retired
     * - Must not be unactivated (titles that haven't debuted can't be retired)
     * - Must not have future activation scheduled
     *
     * @param  Model  $entity  The Title entity to validate
     *
     * @throws CannotBeRetiredException When retirement is not allowed
     */
    public function validate(Model $entity): void
    {
        if (! $entity instanceof Title) {
            throw new InvalidArgumentException('TitleRetirementValidation can only validate Title entities');
        }

        if ($entity->isRetired()) {
            throw CannotBeRetiredException::retired();
        }

        if (! $entity->hasActivityPeriods()) {
            throw CannotBeRetiredException::unactivated('title', (string) $entity->name);
        }

        if ($entity->hasFutureActivity()) {
            throw CannotBeRetiredException::hasFutureActivation('title', (string) $entity->name);
        }
    }
}
