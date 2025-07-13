<?php

declare(strict_types=1);

namespace App\Exceptions\Status;

use App\Exceptions\BaseBusinessException;

/**
 * Exception thrown when an entity cannot be released due to business rule violations.
 *
 * This exception handles scenarios where release is prevented by current state
 * or business logic constraints in wrestling promotion contract management.
 *
 * BUSINESS CONTEXT:
 * Release represents the termination of employment contracts while maintaining
 * the possibility of future re-employment. Failed releases can disrupt roster
 * management, payroll calculations, and contractual obligations.
 *
 * COMMON SCENARIOS:
 * - Attempting to release unemployed, already released, or retired individuals
 * - Trying to release entities with future employment commitments
 * - Release conflicts with active championship reigns or storylines
 * - Missing employment prerequisites for proper contract termination
 *
 * BUSINESS IMPACT:
 * - Maintains contract integrity and employment status accuracy
 * - Protects payroll calculations and benefit administration
 * - Ensures proper notice periods and severance compliance
 * - Prevents premature releases that could affect ongoing storylines
 */
class CannotBeReleasedException extends BaseBusinessException
{
    /**
     * Entity is unemployed and cannot be released.
     *
     * @param  string|null  $entityType  Optional entity type for context (e.g., 'wrestler', 'referee')
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function unemployed(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is unemployed and cannot be released.");
    }

    /**
     * Entity is already released and cannot be released again.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function released(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is already released.");
    }

    /**
     * Entity is permanently retired and cannot be released.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function retired(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is retired and cannot be released.");
    }

    /**
     * Entity has future employment and cannot be released before employment begins.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function hasFutureEmployment(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} has not been officially employed and cannot be released.");
    }

    /**
     * Entity cannot be released while holding active championship titles.
     *
     * @param  array<string>  $championshipTitles  List of championship titles currently held
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function activeChampion(array $championshipTitles, ?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';
        $titles = implode(', ', $championshipTitles);

        return new self("This{$context} holds active championships ({$titles}) and cannot be released until titles are vacated.");
    }

    /**
     * Entity cannot be released due to contractual notice period requirements.
     *
     * @param  int  $noticePeriodDays  Number of days required for notice period
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function noticePeriodRequired(int $noticePeriodDays, ?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} requires {$noticePeriodDays} days notice before release can be processed.");
    }
}
