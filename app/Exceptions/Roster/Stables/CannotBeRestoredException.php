<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Stables\Stable;

/**
 * Exception thrown when a stable cannot be restored from soft deletion due to business rule violations.
 *
 * This exception handles scenarios where stable restoration is prevented by current state
 * or business logic constraints in wrestling promotion stable management.
 *
 * BUSINESS CONTEXT:
 * Stable restoration allows previously soft-deleted stables to be brought back into
 * active operations, typically for reunion storylines, nostalgia acts, or correcting
 * administrative errors. This is a significant narrative opportunity that can create
 * compelling storylines while maintaining historical continuity and member relationships.
 *
 * COMMON SCENARIOS:
 * - Attempting to restore stables when original members are unavailable
 * - Trying to restore stables with conflicting current member commitments
 * - Restoration conflicts with active storylines or championship reigns
 * - Administrative constraints preventing restoration without proper authorization
 * - Member compatibility issues or contractual conflicts preventing reunion
 *
 * BUSINESS IMPACT:
 * - Maintains roster stability and prevents member commitment conflicts
 * - Protects ongoing storylines and current stable dynamics
 * - Ensures restored stables have viable member bases for compelling storylines
 * - Prevents administrative errors and unauthorized stable resurrections
 * - Supports proper storyline continuity and fan investment management
 */
final class CannotBeRestoredException extends BaseBusinessException
{
    /**
     * Stable is not soft deleted and cannot be restored.
     *
     * @param  Stable  $stable  The stable that is not deleted
     */
    public static function notDeleted(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is not deleted and cannot be restored.");
    }

    /**
     * Stable name conflicts with an existing active stable.
     *
     * @param  Stable  $stable  The stable being restored
     * @param  string  $conflictingStableName  Name of the conflicting stable
     */
    public static function nameConflict(Stable $stable, string $conflictingStableName): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be restored: name conflicts with existing stable '{$conflictingStableName}'.");
    }

    /**
     * No former members are available for restoration.
     *
     * @param  Stable  $stable  The stable being restored
     */
    public static function noAvailableFormerMembers(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be restored: no former members are currently available.");
    }

    /**
     * Insufficient former members available for viable restoration.
     *
     * @param  Stable  $stable  The stable being restored
     * @param  int  $availableCount  Number of available former members
     * @param  int  $minimumRequired  Minimum members required
     */
    public static function insufficientFormerMembers(Stable $stable, int $availableCount, int $minimumRequired): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be restored: only {$availableCount} former members available, but {$minimumRequired} required.");
    }

    /**
     * Key former members are unavailable for restoration.
     *
     * @param  Stable  $stable  The stable being restored
     * @param  string  $unavailableMembers  Names of unavailable key members
     */
    public static function keyMembersUnavailable(Stable $stable, string $unavailableMembers): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be restored: key former members unavailable: {$unavailableMembers}.");
    }

    /**
     * Former members have conflicting current stable commitments.
     *
     * @param  Stable  $stable  The stable being restored
     * @param  string  $conflictingCommitments  Description of member conflicts
     */
    public static function memberCommitmentConflicts(Stable $stable, string $conflictingCommitments): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be restored due to member commitment conflicts: {$conflictingCommitments}.");
    }

    /**
     * Restoration would conflict with active storylines.
     *
     * @param  Stable  $stable  The stable being restored
     * @param  string  $storylineConflicts  Description of storyline conflicts
     */
    public static function storylineConflicts(Stable $stable, string $storylineConflicts): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be restored due to storyline conflicts: {$storylineConflicts}.");
    }

    /**
     * Restoration requires proper administrative authorization.
     *
     * @param  Stable  $stable  The stable being restored
     * @param  string  $authorizationLevel  Required authorization level
     */
    public static function insufficientAuthorization(Stable $stable, string $authorizationLevel): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be restored without {$authorizationLevel} authorization.");
    }

    /**
     * Stable was permanently disbanded and cannot be restored.
     *
     * @param  Stable  $stable  The stable that was permanently disbanded
     */
    public static function permanentlyDisbanded(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} was permanently disbanded and cannot be restored.");
    }

    /**
     * Restoration timing conflicts with scheduled events.
     *
     * @param  Stable  $stable  The stable being restored
     * @param  string  $eventConflicts  Description of event timing conflicts
     */
    public static function eventTimingConflicts(Stable $stable, string $eventConflicts): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} cannot be restored due to event timing conflicts: {$eventConflicts}.");
    }
}
