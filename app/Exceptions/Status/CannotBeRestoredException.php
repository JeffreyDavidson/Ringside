<?php

declare(strict_types=1);

namespace App\Exceptions\Status;

use App\Exceptions\BaseBusinessException;

/**
 * Exception thrown when an entity cannot be restored due to business rule violations.
 *
 * This exception handles scenarios where restoration from soft deletion is prevented
 * by current state or business logic constraints in wrestling promotion data management.
 *
 * BUSINESS CONTEXT:
 * Restoration represents recovering soft-deleted entities back to active status,
 * often for correcting administrative errors or reversing premature deletions.
 * Failed restorations can prevent data recovery and administrative corrections.
 *
 * COMMON SCENARIOS:
 * - Attempting to restore entities that are not currently deleted
 * - Trying to restore entities with dependency conflicts
 * - Restoration conflicts with replacement entities or updated business rules
 * - Missing prerequisites for proper data recovery workflow
 *
 * BUSINESS IMPACT:
 * - Maintains data integrity and administrative error correction capabilities
 * - Protects against restoration conflicts that could corrupt relationships
 * - Ensures proper audit trails for deletion and restoration activities
 * - Prevents unauthorized data recovery that could affect active operations
 */
class CannotBeRestoredException extends BaseBusinessException
{
    /**
     * Entity is not currently deleted and cannot be restored.
     *
     * @param  string|null  $entityType  Optional entity type for context (e.g., 'wrestler', 'event')
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function notDeleted(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is not deleted and cannot be restored.");
    }

    /**
     * Entity cannot be restored due to dependency conflicts.
     *
     * @param  string  $conflictingDependency  Description of the conflicting dependency
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function dependencyConflict(string $conflictingDependency, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be restored due to dependency conflict: {$conflictingDependency}.");
    }

    /**
     * Entity cannot be restored because a replacement exists.
     *
     * @param  string  $replacementEntity  Description of the replacement entity
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function replacementExists(string $replacementEntity, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be restored because replacement exists: {$replacementEntity}.");
    }

    /**
     * Entity cannot be restored due to hard deletion.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function hardDeleted(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} has been permanently deleted and cannot be restored.");
    }

    /**
     * Entity cannot be restored without proper authorization.
     *
     * @param  string  $authorizationLevel  Required authorization level for restoration
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function unauthorizedRestoration(string $authorizationLevel, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be restored without {$authorizationLevel} authorization.");
    }

    /**
     * Generic restoration failure with custom message.
     *
     * @param  string  $reason  Specific reason why restoration failed
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function withReason(string $reason, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be restored: {$reason}.");
    }
}
