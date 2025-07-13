<?php

declare(strict_types=1);

namespace App\Exceptions\Status;

use App\Exceptions\BaseBusinessException;

/**
 * Exception thrown when an entity cannot be suspended due to business rule violations.
 *
 * This exception handles scenarios where suspension is prevented by current state
 * or business logic constraints in wrestling promotion management.
 *
 * BUSINESS CONTEXT:
 * Suspension represents disciplinary action that temporarily removes entities from
 * active competition while maintaining their employment status. Failed suspensions
 * can undermine disciplinary procedures and regulatory compliance.
 *
 * COMMON SCENARIOS:
 * - Attempting to suspend unemployed, retired, or released entities
 * - Trying to suspend already suspended or injured individuals
 * - Tag team suspension conflicts due to individual member status
 * - Missing employment prerequisites for proper suspension workflow
 *
 * BUSINESS IMPACT:
 * - Maintains disciplinary procedure integrity and regulatory compliance
 * - Protects employment status accuracy and payroll calculations
 * - Ensures proper storyline consequences for character actions
 * - Prevents administrative errors that could affect union relations
 */
class CannotBeSuspendedException extends BaseBusinessException
{
    /**
     * Entity is unemployed and cannot be suspended.
     *
     * @param  string|null  $entityType  Optional entity type for context (e.g., 'wrestler', 'referee')
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function unemployed(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is unemployed and cannot be suspended.");
    }

    /**
     * Entity has future employment and cannot be suspended before official employment begins.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function hasFutureEmployment(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} has not been officially employed and cannot be suspended.");
    }

    /**
     * Entity is permanently retired and cannot be suspended.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function retired(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is retired and cannot be suspended.");
    }

    /**
     * Entity is released and cannot be suspended.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function released(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is released and cannot be suspended.");
    }

    /**
     * Entity is already suspended and cannot be suspended again.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function suspended(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is already suspended.");
    }

    /**
     * Entity is injured and cannot be suspended while recovering.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function injured(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is injured and cannot be suspended.");
    }

    /**
     * Tag team has no active wrestlers and cannot be suspended.
     *
     * @param  string|null  $tagTeamName  Optional tag team name for specific error messaging
     */
    public static function noActiveWrestlers(?string $tagTeamName = null): static
    {
        $context = $tagTeamName ? " team '{$tagTeamName}'" : ' tag team';

        return new self("This{$context} has no active wrestlers and cannot be suspended.");
    }

    /**
     * Tag team cannot be suspended because a wrestler is already suspended.
     *
     * @param  string  $wrestlerName  Name of the wrestler who is already suspended
     * @param  string|null  $tagTeamName  Optional tag team name for context
     */
    public static function wrestlerAlreadySuspended(string $wrestlerName, ?string $tagTeamName = null): static
    {
        $context = $tagTeamName ? " team '{$tagTeamName}'" : ' team';

        return new self("Tag{$context} cannot be suspended because wrestler '{$wrestlerName}' is already suspended.");
    }

    /**
     * Tag team cannot be suspended because a wrestler is injured.
     *
     * @param  string  $wrestlerName  Name of the injured wrestler
     * @param  string|null  $tagTeamName  Optional tag team name for context
     */
    public static function wrestlerInjured(string $wrestlerName, ?string $tagTeamName = null): static
    {
        $context = $tagTeamName ? " team '{$tagTeamName}'" : ' team';

        return new self("Tag{$context} cannot be suspended because wrestler '{$wrestlerName}' is injured.");
    }

    /**
     * Tag team cannot be suspended because a wrestler cannot be suspended.
     *
     * @param  string  $wrestlerName  Name of the wrestler who cannot be suspended
     * @param  string|null  $tagTeamName  Optional tag team name for context
     * @param  string|null  $reason  Optional reason why the wrestler cannot be suspended
     */
    public static function wrestlerCannotBeSuspended(string $wrestlerName, ?string $tagTeamName = null, ?string $reason = null): static
    {
        $context = $tagTeamName ? " team '{$tagTeamName}'" : ' team';
        $reasonText = $reason ? " ({$reason})" : '';

        return new self("Tag{$context} cannot be suspended because wrestler '{$wrestlerName}' cannot be suspended{$reasonText}.");
    }

    /**
     * Entity cannot be suspended due to active championship reign.
     *
     * @param  string  $championshipTitle  Name of the championship currently held
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function activeChampion(string $championshipTitle, ?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} holds the {$championshipTitle} and cannot be suspended during their reign.");
    }
}
