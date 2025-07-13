<?php

declare(strict_types=1);

namespace App\Exceptions\Status;

use App\Exceptions\BaseBusinessException;

/**
 * Exception thrown when an entity cannot be employed due to business rule violations.
 *
 * This exception handles scenarios where employment is prevented by current state
 * or business logic constraints in the wrestling promotion.
 *
 * BUSINESS CONTEXT:
 * Employment represents the active contractual relationship between the promotion
 * and wrestlers, referees, managers, or tag teams. Failed employment can disrupt
 * roster management, event planning, and contractual obligations.
 *
 * COMMON SCENARIOS:
 * - Attempting to employ an already employed entity
 * - Trying to employ retired or suspended individuals
 * - Employment conflicts with existing contracts or obligations
 * - Missing contractual prerequisites for employment
 *
 * BUSINESS IMPACT:
 * - Prevents double employment and contractual conflicts
 * - Maintains roster integrity and employment status accuracy
 * - Protects payroll calculations and appearance fee management
 * - Ensures proper regulatory compliance and union obligations
 */
class CannotBeEmployedException extends BaseBusinessException
{
    /**
     * Entity is already employed and cannot be re-employed.
     *
     * @param  string|null  $entityType  Optional entity type for context (e.g., 'wrestler', 'referee')
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function employed(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is already employed and cannot be re-employed.");
    }

    /**
     * Entity cannot be employed because they are permanently retired.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function retired(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is retired and cannot be employed.");
    }

    /**
     * Entity cannot be employed while currently suspended.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     * @param  string|null  $suspensionReason  Optional reason for suspension
     */
    public static function suspended(?string $entityType = null, ?string $entityName = null, ?string $suspensionReason = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';
        $reason = $suspensionReason ? " ({$suspensionReason})" : '';

        return new self("This{$context} is currently suspended{$reason} and cannot be employed.");
    }

    /**
     * Entity cannot be employed due to contractual conflicts.
     *
     * @param  string  $conflict  Description of the contractual conflict
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function contractualConflict(string $conflict, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be employed due to contractual conflict: {$conflict}.");
    }

    /**
     * Entity cannot be employed due to missing qualifications or requirements.
     *
     * @param  string  $requirement  Description of the missing requirement
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function missingRequirement(string $requirement, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be employed: missing {$requirement}.");
    }
}
