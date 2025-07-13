<?php

declare(strict_types=1);

namespace App\Exceptions\Status;

use App\Exceptions\BaseBusinessException;

/**
 * Exception thrown when an entity cannot be cleared from injury due to business rule violations.
 *
 * This exception handles scenarios where injury clearance is prevented by current state
 * or medical protocol constraints in wrestling promotion safety management.
 *
 * BUSINESS CONTEXT:
 * Injury clearance represents the medical approval for an entity to return to active
 * competition after injury recovery. Failed clearances protect performer safety and
 * maintain medical protocol compliance.
 *
 * COMMON SCENARIOS:
 * - Attempting to clear entities that are not currently injured
 * - Trying to clear injuries without proper medical documentation
 * - Clearance conflicts with ongoing medical treatment requirements
 * - Missing medical prerequisites for proper return-to-competition protocols
 *
 * BUSINESS IMPACT:
 * - Maintains performer safety and medical protocol integrity
 * - Protects against premature returns that could worsen injuries
 * - Ensures proper insurance compliance and liability management
 * - Supports accurate medical record keeping and recovery tracking
 */
class CannotBeClearedFromInjuryException extends BaseBusinessException
{
    /**
     * Entity is not currently injured and cannot be cleared from injury.
     *
     * @param  string|null  $entityType  Optional entity type for context (e.g., 'wrestler', 'referee')
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function notInjured(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is not injured and cannot be cleared from an injury.");
    }

    /**
     * Entity cannot be cleared due to insufficient medical documentation.
     *
     * @param  string  $missingDocumentation  Description of missing medical documentation
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function insufficientMedicalDocumentation(string $missingDocumentation, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be cleared from injury due to insufficient medical documentation: {$missingDocumentation}.");
    }

    /**
     * Entity cannot be cleared due to ongoing medical treatment requirements.
     *
     * @param  string  $ongoingTreatment  Description of ongoing medical treatment
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function ongoingTreatment(string $ongoingTreatment, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be cleared from injury due to ongoing treatment: {$ongoingTreatment}.");
    }

    /**
     * Entity cannot be cleared without proper medical authorization.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function unauthorizedClearance(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} cannot be cleared from injury without proper medical authorization.");
    }
}
