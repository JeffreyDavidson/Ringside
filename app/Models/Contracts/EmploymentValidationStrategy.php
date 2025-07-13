<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Exceptions\Status\CannotBeEmployedException;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract for employment validation strategies.
 *
 * This interface defines the contract for validating employment-related operations
 * across different entity types, allowing for entity-specific business rules.
 */
interface EmploymentValidationStrategy
{
    /**
     * Validate that the entity can be employed.
     *
     * @param  Model  $entity  The entity to validate for employment
     *
     * @throws CannotBeEmployedException When employment is not allowed
     */
    public function validate(Model $entity): void;
}
