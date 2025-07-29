<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Stables\Stable;

/**
 * Exception thrown when a stable cannot be retired due to business rule violations.
 *
 * This exception handles scenarios where stable retirement is prevented by current state
 * or business logic constraints in wrestling promotion stable management.
 *
 * BUSINESS CONTEXT:
 * Stable retirement marks the formal end of a stable's existence, typically due to
 * internal conflicts, member departures, storyline conclusions, or administrative
 * decisions. Unlike disbandment (which can be temporary), retirement is considered
 * permanent and involves comprehensive member status transitions.
 *
 * COMMON SCENARIOS:
 * - Attempting to retire inactive or already disbanded stables
 * - Retiring stables with active championship obligations
 * - Retirement conflicts with ongoing storylines or major events
 * - Member contractual obligations preventing retirement
 * - Administrative constraints requiring proper authorization
 *
 * BUSINESS IMPACT:
 * - Maintains roster stability and prevents premature stable endings
 * - Protects ongoing storylines and championship lineages
 * - Ensures member transitions are properly handled
 * - Supports proper administrative oversight of stable operations
 * - Preserves fan investment and storyline continuity
 */
final class CannotBeRetiredException extends BaseBusinessException
{
    /**
     * Stable is not currently active and cannot be retired.
     *
     * @param  Stable  $stable  The inactive stable
     */
    public static function notActive(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is not currently active and cannot be retired.");
    }

    /**
     * Stable is already retired.
     *
     * @param  Stable  $stable  The already retired stable
     */
    public static function alreadyRetired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is already retired.");
    }

    /**
     * Stable has current members holding championships.
     *
     * @param  Stable  $stable  The stable with championship obligations
     * @param  string  $championshipDetails  Details of current championships
     */
    public static function hasChampionshipObligations(Stable $stable, string $championshipDetails): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be retired due to championship obligations: {$championshipDetails}.");
    }

    /**
     * Stable retirement conflicts with active storylines.
     *
     * @param  Stable  $stable  The stable with storyline conflicts
     * @param  string  $storylineConflicts  Details of conflicting storylines
     */
    public static function storylineConflicts(Stable $stable, string $storylineConflicts): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be retired due to active storylines: {$storylineConflicts}.");
    }

    /**
     * Stable has upcoming scheduled events.
     *
     * @param  Stable  $stable  The stable with scheduled events
     * @param  string  $eventDetails  Details of upcoming events
     */
    public static function hasScheduledEvents(Stable $stable, string $eventDetails): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be retired due to scheduled events: {$eventDetails}.");
    }

    /**
     * Members have contractual obligations preventing retirement.
     *
     * @param  Stable  $stable  The stable with member contract conflicts
     * @param  string  $contractConflicts  Details of contract conflicts
     */
    public static function memberContractConflicts(Stable $stable, string $contractConflicts): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be retired due to member contract conflicts: {$contractConflicts}.");
    }

    /**
     * Retirement requires proper administrative authorization.
     *
     * @param  Stable  $stable  The stable requiring authorization
     * @param  string  $authorizationLevel  Required authorization level
     */
    public static function insufficientAuthorization(Stable $stable, string $authorizationLevel): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be retired without {$authorizationLevel} authorization.");
    }

    /**
     * Stable has unresolved member disciplinary issues.
     *
     * @param  Stable  $stable  The stable with disciplinary issues
     * @param  string  $disciplinaryIssues  Details of unresolved issues
     */
    public static function unresolvedDisciplinaryIssues(Stable $stable, string $disciplinaryIssues): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be retired due to unresolved disciplinary issues: {$disciplinaryIssues}.");
    }

    /**
     * Retirement timing conflicts with major events.
     *
     * @param  Stable  $stable  The stable with timing conflicts
     * @param  string  $majorEventConflicts  Details of major event conflicts
     */
    public static function majorEventConflicts(Stable $stable, string $majorEventConflicts): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be retired due to major event conflicts: {$majorEventConflicts}.");
    }

    /**
     * Stable members are involved in active feuds.
     *
     * @param  Stable  $stable  The stable with active feuds
     * @param  string  $feudDetails  Details of active feuds
     */
    public static function activeFeudInvolvement(Stable $stable, string $feudDetails): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be retired due to active feud involvement: {$feudDetails}.");
    }
}