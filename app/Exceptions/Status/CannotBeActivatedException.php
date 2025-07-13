<?php

declare(strict_types=1);

namespace App\Exceptions\Status;

use App\Exceptions\BaseBusinessException;

/**
 * Exception thrown when an entity cannot be reactivated due to business rule violations.
 *
 * This exception covers scenarios where reactivation is prevented by the current state
 * or business logic constraints of previously active wrestling promotion entities.
 *
 * BUSINESS CONTEXT:
 * Activation represents bringing a previously deactivated entity back into active competition.
 * This is different from debut (first-time activation). Failed reactivations can disrupt
 * comeback storylines, championship reinstatements, and return narratives.
 *
 * COMMON SCENARIOS:
 * - Attempting to activate an already active title or stable
 * - Trying to reactivate entities that are permanently retired
 * - Activation conflicts with existing business rules or replacement entities
 * - Missing prerequisites for proper reactivation workflow
 *
 * BUSINESS IMPACT:
 * - Prevents invalid championship reactivations and duplicate title defenses
 * - Maintains entity lifecycle integrity and historical consistency
 * - Protects comeback storylines and return event planning
 * - Ensures proper regulatory compliance and reactivation protocols
 */
class CannotBeActivatedException extends BaseBusinessException
{
    /**
     * Entity is already in an active state and cannot be reactivated.
     *
     * @param  string|null  $entityType  Optional entity type for context (e.g., 'title', 'stable')
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function activated(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is already activated and cannot be reactivated.");
    }

    /**
     * @deprecated Use activated() instead for consistency
     */
    public static function alreadyActivated(): static
    {
        return self::activated();
    }

    /**
     * Entity cannot be reactivated because it has been permanently retired.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function retired(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is retired and cannot be reactivated.");
    }

    /**
     * Entity has never been activated and cannot be reactivated.
     *
     * Use debut workflow instead for first-time activation.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function neverActivated(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} has never been activated and cannot be reactivated. Use debut workflow instead.");
    }

    /**
     * Entity cannot be reactivated due to replacement entity conflicts.
     *
     * @param  string  $replacementEntity  Description of the replacement entity
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function replacementExists(string $replacementEntity, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be reactivated because replacement exists: {$replacementEntity}.");
    }

    /**
     * Entity cannot be reactivated due to missing required prerequisites.
     *
     * @param  string  $prerequisite  Description of the missing prerequisite
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function missingPrerequisite(string $prerequisite, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be reactivated: {$prerequisite}.");
    }

    /**
     * Entity cannot be reactivated due to business rule conflicts.
     *
     * @param  string  $conflict  Description of the business rule conflict
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function businessRuleConflict(string $conflict, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be reactivated due to conflict: {$conflict}.");
    }

    /**
     * Entity cannot be reactivated without proper authorization.
     *
     * @param  string  $authorizationLevel  Required authorization level for reactivation
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function unauthorizedReactivation(string $authorizationLevel, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be reactivated without {$authorizationLevel} authorization.");
    }
}
