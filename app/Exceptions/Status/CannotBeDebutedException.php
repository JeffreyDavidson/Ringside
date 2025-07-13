<?php

declare(strict_types=1);

namespace App\Exceptions\Status;

use App\Exceptions\BaseBusinessException;

/**
 * Exception thrown when an entity cannot be debuted due to business rule violations.
 *
 * This exception covers scenarios where first-time debut is prevented by the current state
 * or business logic constraints of the wrestling promotion entity.
 *
 * BUSINESS CONTEXT:
 * Debut represents the first-time introduction of an entity into active competition.
 * This is a significant milestone for titles establishing their inaugural lineage and
 * stables making their first appearance. Failed debuts can disrupt promotional launches
 * and inaugural storylines.
 *
 * COMMON SCENARIOS:
 * - Attempting to debut an entity that has already been debuted previously
 * - Trying to debut entities that are retired or in invalid states
 * - Debut conflicts with existing business rules or championship structures
 * - Missing prerequisites for proper inaugural debut workflow
 *
 * BUSINESS IMPACT:
 * - Protects the integrity of "first-time" debut marketing and storylines
 * - Maintains accurate championship lineage tracking from inception
 * - Ensures proper stable formation ceremonies and member introductions
 * - Prevents confusion between debuts and reactivations in promotional materials
 */
class CannotBeDebutedException extends BaseBusinessException
{
    /**
     * Entity has already been debuted and cannot be debuted again.
     *
     * @param  string|null  $entityType  Optional entity type for context (e.g., 'title', 'stable')
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function alreadyDebuted(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} has already been debuted and cannot be debuted again.");
    }

    /**
     * Entity is permanently retired and cannot be debuted.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function retired(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is retired and cannot be debuted.");
    }

    /**
     * Entity cannot be debuted due to missing required prerequisites.
     *
     * @param  string  $prerequisite  Description of the missing prerequisite
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function missingPrerequisite(string $prerequisite, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be debuted: {$prerequisite}.");
    }

    /**
     * Title cannot be debuted due to championship structure conflicts.
     *
     * @param  string  $conflict  Description of the championship conflict
     * @param  string|null  $titleName  Optional title name for context
     */
    public static function championshipConflict(string $conflict, ?string $titleName = null): static
    {
        $context = $titleName ? " title '{$titleName}'" : ' title';

        return new self("This{$context} cannot be debuted due to championship conflict: {$conflict}.");
    }

    /**
     * Stable cannot be debuted due to insufficient members.
     *
     * @param  int  $minimumRequired  Minimum number of members required for stable debut
     * @param  int  $currentCount  Current number of members in the stable
     * @param  string|null  $stableName  Optional stable name for context
     */
    public static function insufficientMembers(int $minimumRequired, int $currentCount, ?string $stableName = null): static
    {
        $context = $stableName ? " stable '{$stableName}'" : ' stable';

        return new self("This{$context} cannot be debuted with {$currentCount} members (minimum {$minimumRequired} required).");
    }

    /**
     * Entity cannot be debuted due to promotional scheduling conflicts.
     *
     * @param  string  $schedulingConflict  Description of the scheduling conflict
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function schedulingConflict(string $schedulingConflict, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be debuted due to scheduling conflict: {$schedulingConflict}.");
    }

    /**
     * Entity cannot be debuted without proper authorization or approval.
     *
     * @param  string  $authorizationLevel  Required authorization level for debut
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function unauthorizedDebut(string $authorizationLevel, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be debuted without {$authorizationLevel} authorization.");
    }
}
