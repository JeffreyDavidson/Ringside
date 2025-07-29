<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Stables\Stable;

/**
 * Exception thrown when a stable cannot be unretired due to business rule violations.
 *
 * This exception handles scenarios where stable unretirement is prevented by current state
 * or business logic constraints in wrestling promotion stable management.
 *
 * BUSINESS CONTEXT:
 * Stable unretirement allows previously retired stables to return to active competition,
 * typically for reunion storylines, nostalgia acts, or special events. This represents
 * significant storyline opportunities but must be carefully managed to maintain roster
 * stability and storyline continuity.
 *
 * COMMON SCENARIOS:
 * - Attempting to unretire stables that aren't currently retired
 * - Trying to unretire when key former members are unavailable
 * - Unretirement conflicts with current storylines or roster commitments
 * - Name conflicts with existing active stables
 * - Administrative constraints preventing unauthorized unretirement
 * - Member contractual conflicts preventing reunion
 *
 * BUSINESS IMPACT:
 * - Maintains roster stability and prevents member commitment conflicts
 * - Protects ongoing storylines and current stable dynamics
 * - Ensures unretired stables have viable member bases for compelling storylines
 * - Prevents administrative errors and unauthorized stable resurrections
 * - Supports proper storyline continuity and fan investment management
 */
final class CannotBeUnretiredException extends BaseBusinessException
{
    /**
     * Stable is not currently retired and cannot be unretired.
     *
     * @param  Stable  $stable  The stable that is not retired
     */
    public static function notRetired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is not retired and cannot be unretired.");
    }

    /**
     * Stable name conflicts with an existing active stable.
     *
     * @param  Stable  $stable  The stable being unretired
     * @param  string  $conflictingStableName  Name of the conflicting stable
     */
    public static function nameConflict(Stable $stable, string $conflictingStableName): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be unretired: name conflicts with existing stable '{$conflictingStableName}'.");
    }

    /**
     * No former members are available for unretirement.
     *
     * @param  Stable  $stable  The stable being unretired
     */
    public static function noAvailableFormerMembers(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be unretired: no former members are currently available.");
    }

    /**
     * Insufficient former members available for viable unretirement.
     *
     * @param  Stable  $stable  The stable being unretired
     * @param  int  $availableCount  Number of available former members
     * @param  int  $minimumRequired  Minimum members required
     */
    public static function insufficientFormerMembers(Stable $stable, int $availableCount, int $minimumRequired): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be unretired: only {$availableCount} former members available, but {$minimumRequired} required.");
    }

    /**
     * Key former members are unavailable for unretirement.
     *
     * @param  Stable  $stable  The stable being unretired
     * @param  string  $unavailableMembers  Names of unavailable key members
     */
    public static function keyMembersUnavailable(Stable $stable, string $unavailableMembers): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be unretired: key former members unavailable: {$unavailableMembers}.");
    }

    /**
     * Former members have conflicting current commitments.
     *
     * @param  Stable  $stable  The stable being unretired
     * @param  string  $conflictingCommitments  Description of member conflicts
     */
    public static function memberCommitmentConflicts(Stable $stable, string $conflictingCommitments): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be unretired due to member commitment conflicts: {$conflictingCommitments}.");
    }

    /**
     * Unretirement would conflict with active storylines.
     *
     * @param  Stable  $stable  The stable being unretired
     * @param  string  $storylineConflicts  Description of storyline conflicts
     */
    public static function storylineConflicts(Stable $stable, string $storylineConflicts): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be unretired due to storyline conflicts: {$storylineConflicts}.");
    }

    /**
     * Unretirement requires proper administrative authorization.
     *
     * @param  Stable  $stable  The stable being unretired
     * @param  string  $authorizationLevel  Required authorization level
     */
    public static function insufficientAuthorization(Stable $stable, string $authorizationLevel): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be unretired without {$authorizationLevel} authorization.");
    }

    /**
     * Stable was permanently retired and cannot be unretired.
     *
     * @param  Stable  $stable  The stable that was permanently retired
     */
    public static function permanentlyRetired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} was permanently retired and cannot be unretired.");
    }

    /**
     * Unretirement timing conflicts with scheduled events.
     *
     * @param  Stable  $stable  The stable being unretired
     * @param  string  $eventConflicts  Description of event timing conflicts
     */
    public static function eventTimingConflicts(Stable $stable, string $eventConflicts): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be unretired due to event timing conflicts: {$eventConflicts}.");
    }
}
