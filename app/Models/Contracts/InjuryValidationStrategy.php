<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Exceptions\Status\CannotBeInjuredException;
use Illuminate\Database\Eloquent\Model;

/**
 * Contract for injury validation strategies.
 *
 * This interface defines the contract for validating injury-related operations
 * across different entity types, allowing for entity-specific business rules.
 */
interface InjuryValidationStrategy
{
    /**
     * Validate that the entity can be injured.
     *
     * @param  Model  $entity  The entity to validate for injury
     *
     * @throws CannotBeInjuredException When injury is not allowed
     */
    public function validate(Model $entity): void;
}
