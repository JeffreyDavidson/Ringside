<?php

declare(strict_types=1);

namespace App\Exceptions\Status;

use App\Exceptions\BaseBusinessException;

/**
 * Exception thrown when an entity cannot be unretired due to business rule violations.
 *
 * This exception handles scenarios where unretirement is prevented by current state
 * or business logic constraints in wrestling promotion comeback management.
 *
 * BUSINESS CONTEXT:
 * Unretirement represents bringing a retired entity back to active competition,
 * often for special occasions, comebacks, or storyline purposes. Failed unretirements
 * can disrupt comeback narratives and special event planning.
 *
 * COMMON SCENARIOS:
 * - Attempting to unretire entities that are not currently retired
 * - Trying to unretire entities with permanent retirement status
 * - Unretirement conflicts with age restrictions or medical clearances
 * - Missing prerequisites for proper comeback workflow approval
 *
 * BUSINESS IMPACT:
 * - Maintains retirement status integrity and career timeline accuracy
 * - Protects comeback storylines and special event marketing value
 * - Ensures proper medical clearances for returning performers
 * - Prevents unauthorized comebacks that could devalue retirement ceremonies
 */
class CannotBeUnretiredException extends BaseBusinessException
{
    /**
     * Entity is not currently retired and cannot be unretired.
     *
     * @param  string|null  $entityType  Optional entity type for context (e.g., 'wrestler', 'title')
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function notRetired(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is not retired and cannot be unretired.");
    }

    /**
     * Entity has permanent retirement status and cannot be unretired.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function permanentRetirement(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} has permanent retirement status and cannot be unretired.");
    }

    /**
     * Entity cannot be unretired due to medical restrictions.
     *
     * @param  string  $medicalRestriction  Description of the medical restriction
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function medicalRestriction(string $medicalRestriction, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be unretired due to medical restriction: {$medicalRestriction}.");
    }

    /**
     * Entity cannot be unretired without proper authorization.
     *
     * @param  string  $authorizationLevel  Required authorization level for unretirement
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function unauthorizedUnretirement(string $authorizationLevel, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be unretired without {$authorizationLevel} authorization.");
    }

    /**
     * Entity cannot be unretired due to contractual limitations.
     *
     * @param  string  $contractualLimitation  Description of the contractual limitation
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function contractualLimitation(string $contractualLimitation, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be unretired due to contractual limitation: {$contractualLimitation}.");
    }
}
