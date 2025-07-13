<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Exceptions\Status\CannotBeSuspendedException;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract for suspension validation strategies.
 *
 * This interface defines the contract for validating suspension-related operations
 * across different entity types, allowing for entity-specific business rules.
 */
interface SuspensionValidationStrategy
{
    /**
     * Validate that the entity can be suspended.
     *
     * @param  Model  $entity  The entity to validate for suspension
     *
     * @throws CannotBeSuspendedException When suspension is not allowed
     */
    public function validate(Model $entity): void;
}
