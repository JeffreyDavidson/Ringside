<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\Stables;

use App\Exceptions\BaseBusinessException;
use App\Models\Stables\Stable;

/**
 * Exception thrown when a stable cannot be established due to business rule violations.
 *
 * This exception handles scenarios where stable establishment is prevented by current state
 * or business logic constraints in wrestling promotion stable management.
 *
 * BUSINESS CONTEXT:
 * Stable establishment represents the formal creation and activation of a wrestling faction,
 * marking the beginning of collective storylines and member alliances. This is a significant
 * creative decision that affects multiple roster members simultaneously and establishes new
 * dynamics for feuds, championships, and character development. Proper establishment procedures
 * ensure narrative coherence while maximizing booking flexibility and storyline potential.
 *
 * COMMON SCENARIOS:
 * - Attempting to establish an already active or previously established stable
 * - Trying to establish stables with conflicting member commitments or availability issues
 * - Establishment conflicts with existing business rules, storylines, or member obligations
 * - Missing prerequisites for proper stable formation such as member availability or approval
 * - Administrative errors involving insufficient members or incompatible roster configurations
 * - Establishment attempts during periods of member instability or contractual complications
 *
 * BUSINESS IMPACT:
 * - Maintains stable lifecycle integrity and member relationship consistency across storylines
 * - Protects storyline planning investments and multi-member narrative development strategies
 * - Ensures proper member allocation and commitment procedures to prevent booking conflicts
 * - Prevents conflicts with active feuds, championship pursuits, and existing stable dynamics
 * - Supports accurate stable management records and faction formation documentation
 * - Maintains creative flexibility for future stable interactions and storyline developments
 */
final class CannotBeEstablishedException extends BaseBusinessException
{
    /**
     * Stable is already established and cannot be re-established.
     *
     * @param  Stable  $stable  The stable that cannot be established
     */
    public static function established(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new self("{$context} is already established and cannot be re-established.");
    }

    /**
     * Stable cannot be established because it has been permanently retired.
     *
     * @param  Stable  $stable  The stable that cannot be established
     */
    public static function retired(Stable $stable): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} is retired and cannot be established.");
    }

    /**
     * Stable cannot be established due to member availability conflicts.
     *
     * @param  Stable  $stable  The stable that cannot be established
     * @param  string  $conflict  Description of the member availability conflict
     */
    public static function memberConflict(Stable $stable, string $conflict): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be established due to member conflict: {$conflict}.");
    }

    /**
     * Stable cannot be established due to missing required members.
     *
     * @param  Stable  $stable  The stable that cannot be established
     * @param  int  $required  Number of members required
     * @param  int  $available  Number of members currently available
     */
    public static function insufficientMembers(Stable $stable, int $required, int $available): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be established: requires {$required} members but only {$available} available.");
    }

    /**
     * Stable cannot be established due to business rule conflicts.
     *
     * @param  Stable  $stable  The stable that cannot be established
     * @param  string  $conflict  Description of the business rule conflict
     */
    public static function businessRuleConflict(Stable $stable, string $conflict): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be established due to conflict: {$conflict}.");
    }

    /**
     * Stable cannot be established without proper authorization.
     *
     * @param  Stable  $stable  The stable that cannot be established
     * @param  string  $authorizationLevel  Required authorization level for establishment
     */
    public static function unauthorizedEstablishment(Stable $stable, string $authorizationLevel): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be established without {$authorizationLevel} authorization.");
    }

    /**
     * Stable cannot be established due to scheduling conflicts.
     *
     * @param  Stable  $stable  The stable that cannot be established
     * @param  string  $conflictDetails  Description of the scheduling conflict
     */
    public static function schedulingConflict(Stable $stable, string $conflictDetails): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be established due to scheduling conflict: {$conflictDetails}.");
    }

    /**
     * Stable cannot be established due to conflicting existing stable memberships.
     *
     * @param  Stable  $stable  The stable that cannot be established
     * @param  string  $existingStableConflicts  Description of existing stable conflicts
     */
    public static function existingStableConflicts(Stable $stable, string $existingStableConflicts): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be established due to existing stable conflicts: {$existingStableConflicts}.");
    }

    /**
     * Stable cannot be established due to member contractual restrictions.
     *
     * @param  Stable  $stable  The stable that cannot be established
     * @param  string  $contractualRestrictions  Description of contractual restrictions
     */
    public static function memberContractualRestrictions(Stable $stable, string $contractualRestrictions): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be established due to member contractual restrictions: {$contractualRestrictions}.");
    }

    /**
     * Stable cannot be established during member injury or medical absence.
     *
     * @param  Stable  $stable  The stable that cannot be established
     * @param  string  $memberMedicalIssues  Description of member medical issues
     */
    public static function memberMedicalIssues(Stable $stable, string $memberMedicalIssues): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be established due to member medical issues: {$memberMedicalIssues}.");
    }

    /**
     * Stable cannot be established due to ongoing disciplinary actions against members.
     *
     * @param  Stable  $stable  The stable that cannot be established
     * @param  string  $disciplinaryIssues  Description of disciplinary issues
     */
    public static function memberDisciplinaryIssues(Stable $stable, string $disciplinaryIssues): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be established due to member disciplinary issues: {$disciplinaryIssues}.");
    }

    /**
     * Stable cannot be established due to incompatible member character alignments.
     *
     * @param  Stable  $stable  The stable that cannot be established
     * @param  string  $alignmentConflicts  Description of character alignment conflicts
     */
    public static function characterAlignmentConflicts(Stable $stable, string $alignmentConflicts): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be established due to character alignment conflicts: {$alignmentConflicts}.");
    }

    /**
     * Stable cannot be established during active tournament or special event periods.
     *
     * @param  Stable  $stable  The stable that cannot be established
     * @param  string  $eventDetails  Description of the active event
     */
    public static function activeEventPeriod(Stable $stable, string $eventDetails): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be established during active event period: {$eventDetails}.");
    }

    /**
     * Stable cannot be reunited due to insufficient available former members.
     *
     * @param  Stable  $stable  The stable that cannot be reunited
     * @param  int  $required  Number of members required for reunion
     * @param  int  $available  Number of former members currently available
     */
    public static function insufficientFormerMembers(Stable $stable, int $required, int $available): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be reunited: requires {$required} members but only {$available} former members are available.");
    }

    /**
     * Stable cannot be reunited because key former members are unavailable.
     *
     * @param  Stable  $stable  The stable that cannot be reunited
     * @param  string  $unavailableMembers  Description of unavailable key members
     */
    public static function keyFormerMembersUnavailable(Stable $stable, string $unavailableMembers): static
    {
        $context = self::formatModelContext($stable);

        return new static("{$context} cannot be reunited because key former members are unavailable: {$unavailableMembers}.");
    }
}
