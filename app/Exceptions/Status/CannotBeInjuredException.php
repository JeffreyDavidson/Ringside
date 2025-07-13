<?php

declare(strict_types=1);

namespace App\Exceptions\Status;

use App\Exceptions\BaseBusinessException;

/**
 * Exception thrown when an entity cannot be injured due to business rule violations.
 *
 * This exception handles scenarios where injury assignment is prevented by current state
 * or business logic constraints in wrestling promotion safety management.
 *
 * BUSINESS CONTEXT:
 * Injuries represent real physical conditions that affect wrestler availability and
 * safety protocols. Failed injury assignments can compromise medical tracking,
 * insurance claims, and performer safety procedures.
 *
 * COMMON SCENARIOS:
 * - Attempting to injure unemployed, released, or retired individuals
 * - Trying to injure already injured or suspended personnel
 * - Injury conflicts with employment status or contractual obligations
 * - Missing employment prerequisites for proper medical coverage
 *
 * BUSINESS IMPACT:
 * - Maintains medical record accuracy and insurance compliance
 * - Protects worker safety protocols and liability management
 * - Ensures proper storyline injury integration and recovery timelines
 * - Prevents administrative errors that could affect medical benefits
 */
class CannotBeInjuredException extends BaseBusinessException
{
    /**
     * Entity is unemployed and cannot be injured.
     *
     * @param  string|null  $entityType  Optional entity type for context (e.g., 'wrestler', 'referee')
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function unemployed(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is unemployed and cannot be injured.");
    }

    /**
     * Entity is released and cannot be injured.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function released(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is released and cannot be injured.");
    }

    /**
     * Entity is permanently retired and cannot be injured.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function retired(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is retired and cannot be injured.");
    }

    /**
     * Entity has future employment and cannot be injured before employment begins.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function hasFutureEmployment(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} has not been officially employed and cannot be injured.");
    }

    /**
     * Entity is already injured and cannot be injured again.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     * @param  string|null  $currentInjury  Optional description of current injury
     */
    public static function injured(?string $entityType = null, ?string $entityName = null, ?string $currentInjury = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';
        $injury = $currentInjury ? " ({$currentInjury})" : '';

        return new self("This{$context} is already currently injured{$injury}.");
    }

    /**
     * Entity is suspended and cannot be injured while under suspension.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     * @param  string|null  $suspensionReason  Optional reason for suspension
     */
    public static function suspended(?string $entityType = null, ?string $entityName = null, ?string $suspensionReason = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';
        $reason = $suspensionReason ? " ({$suspensionReason})" : '';

        return new self("This{$context} is suspended{$reason} and cannot be injured.");
    }

    /**
     * Entity cannot be injured due to insufficient medical coverage.
     *
     * @param  string  $coverageIssue  Description of the medical coverage issue
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function insufficientMedicalCoverage(string $coverageIssue, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be injured due to medical coverage issue: {$coverageIssue}.");
    }
}
