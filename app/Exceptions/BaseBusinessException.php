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
 * USAGE PATTERN:
 * All business exceptions should extend this class and use the provided utilities
 * for building contextual error messages and extracting model information.
 *
 * @example
 * ```php
 * class CannotBeEmployedException extends BaseBusinessException
 * {
 *     public static function employed(?string $entityType = null, ?string $entityName = null): self
 *     {
 *         $context = self::buildContext($entityType, $entityName);
 *         return new self("This{$context} is already employed and cannot be re-employed.");
 *     }
 * }
 * ```
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
