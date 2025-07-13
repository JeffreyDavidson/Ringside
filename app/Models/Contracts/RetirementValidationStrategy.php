<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Exceptions\Status\CannotBeRetiredException;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract for retirement validation strategies.
 *
 * This interface defines the contract for validating retirement-related operations
 * across different entity types, allowing for entity-specific business rules.
 */
interface RetirementValidationStrategy
{
    /**
     * Validate that the entity can be retired.
     *
     * @param  Model  $entity  The entity to validate for retirement
     *
     * @throws CannotBeRetiredException When retirement is not allowed
     */
    public function validate(Model $entity): void;
}
