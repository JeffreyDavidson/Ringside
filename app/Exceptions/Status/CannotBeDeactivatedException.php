<?php

declare(strict_types=1);

namespace App\Exceptions\Status;

use App\Exceptions\BaseBusinessException;

/**
 * Exception thrown when an entity cannot be deactivated due to business rule violations.
 *
 * This exception handles scenarios where deactivation is prevented by current state
 * or business logic constraints in wrestling promotion entity management.
 *
 * BUSINESS CONTEXT:
 * Deactivation represents temporarily removing an entity from active status while
 * preserving their historical records and potential for reactivation. Failed
 * deactivations can disrupt administrative processes and status tracking.
 *
 * COMMON SCENARIOS:
 * - Attempting to deactivate entities that are not currently active
 * - Trying to deactivate already deactivated or retired entities
 * - Deactivation conflicts with future scheduled activations
 * - Missing active status prerequisites for proper deactivation workflow
 *
 * BUSINESS IMPACT:
 * - Maintains entity status accuracy and administrative consistency
 * - Protects activation scheduling and timeline management
 * - Ensures proper historical record keeping and status transitions
 * - Prevents administrative errors that could affect event planning
 */
class CannotBeDeactivatedException extends BaseBusinessException
{
    /**
     * Entity is not currently activated and cannot be deactivated.
     *
     * @param  string|null  $entityType  Optional entity type for context (e.g., 'title', 'stable')
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function notActivated(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is not currently activated.");
    }

    /**
     * Entity is already deactivated and cannot be deactivated again.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function alreadyDeactivated(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is already deactivated.");
    }

    /**
     * @deprecated Use notActivated() instead for consistency
     */
    public static function unactivated(): static
    {
        return self::notActivated();
    }

    /**
     * Entity has future activation scheduled and cannot be deactivated.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function hasFutureActivation(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} has a future activation scheduled and cannot be deactivated.");
    }

    /**
     * Entity is permanently retired and cannot be deactivated.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function retired(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is retired and cannot be deactivated.");
    }

    /**
     * Entity cannot be deactivated due to active dependencies or commitments.
     *
     * @param  string  $dependency  Description of the active dependency preventing deactivation
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function activeDependency(string $dependency, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be deactivated due to active dependency: {$dependency}.");
    }
}
