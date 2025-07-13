<?php

declare(strict_types=1);

namespace App\Exceptions\Status;

use App\Exceptions\BaseBusinessException;

/**
 * Exception thrown when an entity cannot be retired due to business rule violations.
 *
 * This exception handles scenarios where retirement is prevented by current state
 * or business logic constraints in wrestling promotion lifecycle management.
 *
 * BUSINESS CONTEXT:
 * Retirement represents the permanent end of an entity's active career or operation,
 * marking their transition from active competition to legend status. Failed retirements
 * can disrupt career closure ceremonies and historical record keeping.
 *
 * COMMON SCENARIOS:
 * - Attempting to retire unemployed, unactivated, or already retired entities
 * - Trying to retire entities with future employment or activation commitments
 * - Tag team retirement conflicts due to individual member status issues
 * - Missing career prerequisites for proper retirement ceremony workflow
 *
 * BUSINESS IMPACT:
 * - Maintains career lifecycle integrity and historical accuracy
 * - Protects retirement ceremony planning and Hall of Fame eligibility
 * - Ensures proper pension and benefit calculations for retirees
 * - Preserves legacy storylines and character development continuity
 */
class CannotBeRetiredException extends BaseBusinessException
{
    /**
     * Entity is unemployed and cannot be retired.
     *
     * @param  string|null  $entityType  Optional entity type for context (e.g., 'wrestler', 'referee')
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function unemployed(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is unemployed and cannot be retired.");
    }

    /**
     * Entity is released and cannot be retired.
     *
     * @param  string|null  $entityType  Optional entity type for context (e.g., 'wrestler', 'referee')
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function released(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is released and cannot be retired.");
    }

    /**
     * Entity is unactivated and cannot be retired.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function unactivated(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is unactivated and cannot be retired.");
    }

    /**
     * Entity has future employment and cannot be retired before employment begins.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function hasFutureEmployment(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} has not been officially employed and cannot be retired.");
    }

    /**
     * Entity is already retired and cannot be retired again.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function retired(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is already retired.");
    }

    /**
     * Entity has future activation and cannot be retired before activation begins.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function hasFutureActivation(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} has not been officially activated and cannot be retired.");
    }

    /**
     * Tag team has no active wrestlers and cannot be retired.
     *
     * @param  string|null  $tagTeamName  Optional tag team name for specific error messaging
     */
    public static function noActiveWrestlers(?string $tagTeamName = null): static
    {
        $context = $tagTeamName ? " team '{$tagTeamName}'" : ' tag team';

        return new self("This{$context} has no active wrestlers and cannot be retired.");
    }

    /**
     * Tag team cannot be retired because a wrestler is injured.
     *
     * @param  string  $wrestlerName  Name of the injured wrestler preventing retirement
     * @param  string|null  $tagTeamName  Optional tag team name for context
     */
    public static function wrestlerInjured(string $wrestlerName, ?string $tagTeamName = null): static
    {
        $context = $tagTeamName ? " team '{$tagTeamName}'" : ' team';

        return new self("Tag{$context} cannot be retired because wrestler '{$wrestlerName}' is injured.");
    }

    /**
     * Tag team cannot be retired because a wrestler is suspended.
     *
     * @param  string  $wrestlerName  Name of the suspended wrestler preventing retirement
     * @param  string|null  $tagTeamName  Optional tag team name for context
     */
    public static function wrestlerSuspended(string $wrestlerName, ?string $tagTeamName = null): static
    {
        $context = $tagTeamName ? " team '{$tagTeamName}'" : ' team';

        return new self("Tag{$context} cannot be retired because wrestler '{$wrestlerName}' is suspended.");
    }

    /**
     * Tag team cannot be retired because a wrestler cannot be retired.
     *
     * @param  string  $wrestlerName  Name of the wrestler who cannot be retired
     * @param  string|null  $tagTeamName  Optional tag team name for context
     * @param  string|null  $reason  Optional reason why the wrestler cannot be retired
     */
    public static function wrestlerCannotBeRetired(string $wrestlerName, ?string $tagTeamName = null, ?string $reason = null): static
    {
        $context = $tagTeamName ? " team '{$tagTeamName}'" : ' team';
        $reasonText = $reason ? " ({$reason})" : '';

        return new self("Tag{$context} cannot be retired because wrestler '{$wrestlerName}' cannot be retired{$reasonText}.");
    }

    /**
     * Entity cannot be retired while holding active championship titles.
     *
     * @param  array<string>  $championshipTitles  List of championship titles currently held
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function activeChampion(array $championshipTitles, ?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';
        $titles = implode(', ', $championshipTitles);

        return new self("This{$context} holds active championships ({$titles}) and cannot be retired until titles are vacated.");
    }

    /**
     * Entity cannot be retired due to unresolved contractual obligations.
     *
     * @param  string  $contractualObligation  Description of the unresolved obligation
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function contractualObligation(string $contractualObligation, ?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} cannot be retired due to unresolved contractual obligation: {$contractualObligation}.");
    }
}
