<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Stables\Stable;

/**
 * Exception thrown when a stable cannot be reunited due to business rule violations.
 *
 * This exception handles scenarios where stable reunion is prevented by current state
 * or business logic constraints in wrestling promotion stable management.
 *
 * BUSINESS CONTEXT:
 * Stable reunion allows previously disbanded stables to return to active competition,
 * typically for comeback storylines, nostalgia acts, or member reconciliation narratives.
 * This represents significant storyline opportunities that require careful validation
 * to ensure compelling and realistic reunion scenarios.
 *
 * COMMON SCENARIOS:
 * - Attempting to reunite stables that were never active before
 * - Trying to reunite already active or retired stables
 * - Reunion when key former members are unavailable or committed elsewhere
 * - Conflicting storylines that prevent meaningful reunion
 * - Administrative constraints requiring proper authorization
 * - Member compatibility issues preventing effective reunion
 *
 * BUSINESS IMPACT:
 * - Maintains roster stability and prevents member commitment conflicts
 * - Protects ongoing storylines and current stable dynamics
 * - Ensures reunited stables have viable member bases for compelling storylines
 * - Prevents administrative errors and unauthorized stable resurrections
 * - Supports proper storyline continuity and fan investment management
 */
final class CannotBeReunitedException extends BaseBusinessException
{
    /**
     * Stable has never been active and cannot be reunited.
     *
     * @param  Stable  $stable  The stable that was never active
     */
    public static function neverActive(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} has never been active and cannot be reunited. Use establishment instead.");
    }

    /**
     * Stable is currently active and doesn't need reunion.
     *
     * @param  Stable  $stable  The stable that is already active
     */
    public static function currentlyActive(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is currently active and doesn't need reunion.");
    }

    /**
     * Stable is retired and cannot be reunited.
     *
     * @param  Stable  $stable  The retired stable
     */
    public static function retired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is retired and cannot be reunited. Consider unretirement instead.");
    }

    /**
     * Insufficient former members available for viable reunion.
     *
     * @param  Stable  $stable  The stable being reunited
     * @param  int  $availableCount  Number of available former members
     * @param  int  $minimumRequired  Minimum members required
     */
    public static function insufficientFormerMembers(Stable $stable, int $availableCount, int $minimumRequired): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be reunited: only {$availableCount} former members available, but {$minimumRequired} required.");
    }

    /**
     * Key former members are unavailable for reunion.
     *
     * @param  Stable  $stable  The stable being reunited
     * @param  string  $unavailableMembers  Names of unavailable key members
     */
    public static function keyMembersUnavailable(Stable $stable, string $unavailableMembers): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be reunited: key former members unavailable: {$unavailableMembers}.");
    }

    /**
     * Former members have conflicting current commitments.
     *
     * @param  Stable  $stable  The stable being reunited
     * @param  string  $conflictingCommitments  Description of member conflicts
     */
    public static function memberCommitmentConflicts(Stable $stable, string $conflictingCommitments): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be reunited due to member commitment conflicts: {$conflictingCommitments}.");
    }

    /**
     * Reunion would conflict with active storylines.
     *
     * @param  Stable  $stable  The stable being reunited
     * @param  string  $storylineConflicts  Description of storyline conflicts
     */
    public static function storylineConflicts(Stable $stable, string $storylineConflicts): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be reunited due to storyline conflicts: {$storylineConflicts}.");
    }

    /**
     * Reunion requires proper administrative authorization.
     *
     * @param  Stable  $stable  The stable being reunited
     * @param  string  $authorizationLevel  Required authorization level
     */
    public static function insufficientAuthorization(Stable $stable, string $authorizationLevel): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be reunited without {$authorizationLevel} authorization.");
    }

    /**
     * Too soon since disbandment for credible reunion.
     *
     * @param  Stable  $stable  The stable being reunited
     * @param  int  $daysSinceDisbandment  Days since disbandment
     * @param  int  $minimumDays  Minimum days required
     */
    public static function tooSoonSinceDisbandment(Stable $stable, int $daysSinceDisbandment, int $minimumDays): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be reunited: only {$daysSinceDisbandment} days since disbandment, but {$minimumDays} days required for credible reunion.");
    }

    /**
     * Reunion timing conflicts with scheduled events.
     *
     * @param  Stable  $stable  The stable being reunited
     * @param  string  $eventConflicts  Description of event timing conflicts
     */
    public static function eventTimingConflicts(Stable $stable, string $eventConflicts): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be reunited due to event timing conflicts: {$eventConflicts}.");
    }
}