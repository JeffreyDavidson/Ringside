<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Base class for all business logic exceptions in the wrestling promotion management system.
 *
 * This abstract class provides shared utilities for consistent error messaging,
 * context building, and model information extraction across all business exceptions.
 *
 * DESIGN PRINCIPLES:
 * - Consistent error message formatting across all business exceptions
 * - Reusable utilities for common patterns (entity context, model info extraction)
 * - Type-safe helper methods for standardized exception creation
 * - Extensible foundation for future shared exception functionality
 *
 * ========================================
 * EXCEPTION CREATION GUIDE
 * ========================================
 *
 * This section provides a comprehensive guide for creating business exceptions
 * using the established CannotBePulledException pattern as the standard reference.
 *
 * STEP 1: CLASS STRUCTURE AND IMPORTS
 * ```php
 * <?php
 * declare(strict_types=1);
 *
 * namespace App\Exceptions\[Domain];  // e.g., Titles, Roster, Matches
 *
 * use App\Exceptions\BaseBusinessException;
 * use App\Models\[ModelType]\[ModelClass];  // e.g., App\Models\Titles\Title
 *
 * final class Cannot[Action]Exception extends BaseBusinessException
 * {
 *     // Methods go here
 * }
 * ```
 *
 * STEP 2: COMPREHENSIVE DOCBLOCK
 * Every exception class must include comprehensive business documentation:
 * ```php
 * /**
 *  * Exception thrown when [entity] cannot [action] due to business rule violations.
 *  *
 *  * This exception handles scenarios where [action] is prevented by current state
 *  * or business logic constraints in wrestling promotion [domain] management.
 *  *
 *  * BUSINESS CONTEXT:
 *  * [Detailed explanation of the business operation, its importance, and relationship
 *  * to other business processes. Include domain-specific terminology and concepts.]
 *  *
 *  * COMMON SCENARIOS:
 *  * - [Specific business scenario 1 with context]
 *  * - [Specific business scenario 2 with context]
 *  * - [Specific business scenario 3 with context]
 *  * - [Additional scenarios as needed]
 *  *
 *  * BUSINESS IMPACT:
 *  * - [How this exception protects business integrity]
 *  * - [What operations or data consistency is maintained]
 *  * - [Regulatory or contractual compliance protected]
 *  * - [User experience or operational impact prevented]
 *  *\/
 * ```
 *
 * STEP 3: STATIC FACTORY METHODS
 * Use model-aware parameters with proper type hints:
 * ```php
 * /**
 *  * [Entity] [specific condition] and cannot [action].
 *  *
 *  * @param  ModelClass  $entity  The [entity] that cannot [action]
 *  * @param  string|null  $additionalContext  Optional additional context
 *  *\/
 * public static function specificCondition(ModelClass $entity, ?string $additionalContext = null): static
 * {
 *     $context = self::formatModelContext($entity);
 *     $extra = $additionalContext ? " ({$additionalContext})" : '';
 *
 *     return new self("{$context} [specific condition description]{$extra} and cannot [action].");
 * }
 * ```
 *
 * STEP 4: COMPLETE REFERENCE IMPLEMENTATION
 * The CannotBePulledException class demonstrates the complete pattern:
 * ```php
 * <?php
 * namespace App\Exceptions\Titles;
 *
 * use App\Exceptions\BaseBusinessException;
 * use App\Models\Titles\Title;
 *
 * final class CannotBePulledException extends BaseBusinessException
 * {
 *     // Basic state violation - simple condition check
 *     public static function notActive(Title $title): static
 *     {
 *         $context = self::formatModelContext($title);
 *         return new self("{$context} is not currently active and cannot be pulled from competition.");
 *     }
 *
 *     // Business rule violation with additional context
 *     public static function activeChampionshipReign(Title $title, string $championName): static
 *     {
 *         $context = self::formatModelContext($title);
 *         return new static("{$context} is currently held by {$championName} and cannot be pulled during an active championship reign.");
 *     }
 *
 *     // Authorization violation
 *     public static function unauthorizedPull(Title $title, string $authorizationLevel): static
 *     {
 *         $context = self::formatModelContext($title);
 *         return new static("{$context} cannot be pulled without {$authorizationLevel} authorization.");
 *     }
 *
 *     // Complex business scenario with detailed context
 *     public static function tournamentInvolvement(Title $title, string $eventDetails): static
 *     {
 *         $context = self::formatModelContext($title);
 *         return new static("{$context} is involved in {$eventDetails} and cannot be pulled until the event concludes.");
 *     }
 * }
 * ```
 *
 * METHOD PATTERN TEMPLATES
 * ========================
 *
 * Basic State Violation:
 * ```php
 * public static function invalidState(ModelClass $entity): static
 * {
 *     $context = self::formatModelContext($entity);
 *     return new self("{$context} is in [state] and cannot [action].");
 * }
 * ```
 *
 * Business Rule Violation:
 * ```php
 * public static function businessRule(ModelClass $entity, string $ruleDetails): static
 * {
 *     $context = self::formatModelContext($entity);
 *     return new self("{$context} violates business rule ({$ruleDetails}) and cannot [action].");
 * }
 * ```
 *
 * Authorization Violation:
 * ```php
 * public static function unauthorized(ModelClass $entity, string $requiredLevel): static
 * {
 *     $context = self::formatModelContext($entity);
 *     return new self("{$context} cannot [action] without {$requiredLevel} authorization.");
 * }
 * ```
 *
 * Relationship Conflict:
 * ```php
 * public static function relationshipConflict(ModelClass $entity, ModelClass $conflictingEntity): static
 * {
 *     $entityContext = self::formatModelContext($entity);
 *     $conflictContext = self::formatModelContext($conflictingEntity);
 *     return new self("{$entityContext} has conflict with {$conflictContext} and cannot [action].");
 * }
 * ```
 *
 * BEST PRACTICES
 * ==============
 * 1. Always use `self::formatModelContext($model)` for consistent entity formatting
 * 2. Use model-first parameters for type safety and IDE support
 * 3. Write clear, business-focused error messages that explain the "why"
 * 4. Include comprehensive @param documentation for all method parameters
 * 5. Use `static` return type for static factory methods
 * 6. Group related methods logically (basic violations, business rules, authorization, etc.)
 * 7. Provide additional context parameters when business scenarios require detail
 * 8. Use domain-specific terminology that matches business language
 * 9. Make error messages actionable - suggest what should be done instead
 * 10. Test exception messages for clarity with non-technical stakeholders
 *
 * NAMING CONVENTIONS
 * ==================
 * - Exception Class: Cannot[Action]Exception (e.g., CannotBePulledException)
 * - Static Methods: [specificCondition] (e.g., notActive, businessRule, unauthorized)
 * - Parameters: Use full model types, not generic strings
 * - Namespaces: App\Exceptions\[Domain] where Domain matches the business area
 */
abstract class BaseBusinessException extends Exception
{
    /**
     * Build consistent entity context for error messages.
     *
     * Creates standardized context strings for entities in error messages,
     * providing either specific entity information or generic fallback.
     *
     * @param  string|null  $entityType  Optional entity type (e.g., 'wrestler', 'referee')
     * @param  string|null  $entityName  Optional entity name for specific messaging
     * @return string Formatted context string for use in error messages
     *
     * @example
     * ```php
     * self::buildContext('wrestler', 'John Doe')     // Returns: " wrestler 'John Doe'"
     * self::buildContext('wrestler', null)          // Returns: " wrestler"
     * self::buildContext(null, null)                // Returns: " entity"
     * ```
     */
    protected static function buildContext(?string $entityType, ?string $entityName): string
    {
        if ($entityType && $entityName) {
            return " {$entityType} '{$entityName}'";
        }

        if ($entityType) {
            return " {$entityType}";
        }

        return ' entity';
    }

    /**
     * Extract standardized information from Eloquent models.
     *
     * Provides consistent model information extraction for use in error messages,
     * including name fallback to ID and class type determination.
     *
     * @param  Model  $model  The Eloquent model to extract information from
     * @return array{name: string, type: string, id: mixed} Model information array
     *
     * @example
     * ```php
     * $info = self::extractModelInfo($wrestler);
     * // Returns: ['name' => 'John Doe', 'type' => 'Wrestler', 'id' => 123]
     * ```
     */
    protected static function extractModelInfo(Model $model): array
    {
        return [
            'name' => $model->getAttribute('name') ?? "ID: {$model->getKey()}",
            'type' => class_basename($model),
            'id' => $model->getKey(),
        ];
    }

    /**
     * Format model context for error messages.
     *
     * Creates standardized context strings from Eloquent models for use in
     * error messages, combining type and name information.
     *
     * @param  Model  $model  The Eloquent model to format
     * @return string Formatted context string
     *
     * @example
     * ```php
     * self::formatModelContext($wrestler)  // Returns: "Wrestler 'John Doe'"
     * ```
     */
    protected static function formatModelContext(Model $model): string
    {
        $info = self::extractModelInfo($model);

        return "{$info['type']} '{$info['name']}'";
    }

    /**
     * Format date context for error messages.
     *
     * Provides consistent date formatting for use in error messages,
     * with optional format specification and null handling.
     *
     * @param  Carbon|null  $date  The date to format (null returns empty string)
     * @param  string  $format  Date format string (defaults to 'Y-m-d')
     * @return string Formatted date string or empty string if null
     *
     * @example
     * ```php
     * self::formatDateContext($injuryDate)                    // Returns: "2024-01-15"
     * self::formatDateContext($retirementDate, 'M j, Y')     // Returns: "Jan 15, 2024"
     * self::formatDateContext(null)                          // Returns: ""
     * ```
     */
    protected static function formatDateContext(?Carbon $date, string $format = 'Y-m-d'): string
    {
        return $date ? $date->format($format) : '';
    }

    /**
     * Build context with date information.
     *
     * Combines entity context with date information for comprehensive error messaging,
     * particularly useful for time-based business rule violations.
     *
     * @param  string|null  $entityType  Optional entity type
     * @param  string|null  $entityName  Optional entity name
     * @param  Carbon|null  $date  Optional date for additional context
     * @param  string  $datePrefix  Prefix for date context (e.g., 'since', 'until')
     * @return string Complete context string with optional date information
     *
     * @example
     * ```php
     * self::buildContextWithDate('wrestler', 'John', $injuryDate, 'since')
     * // Returns: " wrestler 'John' since 2024-01-15"
     * ```
     */
    protected static function buildContextWithDate(
        ?string $entityType,
        ?string $entityName,
        ?Carbon $date,
        string $datePrefix = ''
    ): string {
        $context = self::buildContext($entityType, $entityName);

        if ($date) {
            $dateStr = self::formatDateContext($date);
            $prefix = $datePrefix ? " {$datePrefix} " : ' ';
            $context .= "{$prefix}{$dateStr}";
        }

        return $context;
    }

    /**
     * Create standardized "already in state" error message.
     *
     * Provides consistent messaging for scenarios where an entity is already
     * in the target state and cannot transition again.
     *
     * @param  string  $state  The state the entity is already in
     * @param  string|null  $entityType  Optional entity type
     * @param  string|null  $entityName  Optional entity name
     * @return string Standardized error message
     */
    protected static function buildAlreadyInStateMessage(
        string $state,
        ?string $entityType = null,
        ?string $entityName = null
    ): string {
        $context = self::buildContext($entityType, $entityName);

        return "This{$context} is already {$state}.";
    }

    /**
     * Create standardized "cannot due to state" error message.
     *
     * Provides consistent messaging for scenarios where an entity cannot
     * perform an action due to their current state.
     *
     * @param  string  $action  The action that cannot be performed
     * @param  string  $currentState  The current state preventing the action
     * @param  string|null  $entityType  Optional entity type
     * @param  string|null  $entityName  Optional entity name
     * @return string Standardized error message
     */
    protected static function buildCannotDueToStateMessage(
        string $action,
        string $currentState,
        ?string $entityType = null,
        ?string $entityName = null
    ): string {
        $context = self::buildContext($entityType, $entityName);

        return "This{$context} is {$currentState} and cannot be {$action}.";
    }

    /**
     * Create standardized conflict error message.
     *
     * Provides consistent messaging for business rule conflicts with
     * detailed conflict description and resolution guidance.
     *
     * @param  string  $conflictDescription  Description of the specific conflict
     * @param  string|null  $entityType  Optional entity type
     * @param  string|null  $entityName  Optional entity name
     * @param  string|null  $resolution  Optional resolution guidance
     * @return string Standardized conflict error message
     */
    protected static function buildConflictMessage(
        string $conflictDescription,
        ?string $entityType = null,
        ?string $entityName = null,
        ?string $resolution = null
    ): string {
        $context = self::buildContext($entityType, $entityName);
        $message = "This{$context} cannot proceed due to conflict: {$conflictDescription}.";

        if ($resolution) {
            $message .= " {$resolution}";
        }

        return $message;
    }
}
