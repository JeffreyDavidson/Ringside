<?php

declare(strict_types=1);

namespace App\Exceptions\Data;

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;

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
final class CannotBeRestoredException extends BaseBusinessException
{
    /**
     * Entity is not currently deleted and cannot be restored.
     *
     * @param  Model  $entity  The entity that cannot be restored
     */
    public static function notDeleted(Model $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} is not deleted and cannot be restored.");
    }

    /**
     * Entity cannot be restored due to dependency conflicts.
     *
     * @param  Model  $entity  The entity that cannot be restored
     * @param  string  $conflictingDependency  Description of the conflicting dependency
     */
    public static function dependencyConflict(Model $entity, string $conflictingDependency): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} has dependency conflict ({$conflictingDependency}) and cannot be restored.");
    }

    /**
     * Entity cannot be restored because a replacement exists.
     *
     * @param  Model  $entity  The entity that cannot be restored
     * @param  string  $replacementEntity  Description of the replacement entity
     */
    public static function replacementExists(Model $entity, string $replacementEntity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be restored because replacement exists ({$replacementEntity}).");
    }

    /**
     * Entity cannot be restored due to hard deletion.
     *
     * @param  Model  $entity  The entity that cannot be restored
     */
    public static function hardDeleted(Model $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} has been permanently deleted and cannot be restored.");
    }

    /**
     * Entity cannot be restored without proper authorization.
     *
     * @param  Model  $entity  The entity that cannot be restored
     * @param  string  $authorizationLevel  Required authorization level for restoration
     */
    public static function unauthorizedRestoration(Model $entity, string $authorizationLevel): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be restored without {$authorizationLevel} authorization.");
    }

    /**
     * Generic restoration failure with custom message.
     *
     * @param  Model  $entity  The entity that cannot be restored
     * @param  string  $reason  Specific reason why restoration failed
     */
    public static function withReason(Model $entity, string $reason): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be restored: {$reason}.");
    }
}
