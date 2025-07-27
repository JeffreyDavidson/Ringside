<?php

declare(strict_types=1);

namespace App\Exceptions\Roster;

use App\Exceptions\BaseBusinessException;
use App\Models\Contracts\Employable;

/**
 * Exception thrown when a roster member cannot be reinstated due to business rule violations.
 *
 * This exception handles scenarios where reinstatement is prevented by current state
 * or business logic constraints in wrestling promotion disciplinary management.
 *
 * BUSINESS CONTEXT:
 * Reinstatement represents the restoration of a roster member from suspended status back
 * to active competition eligibility. This is a critical disciplinary process that affects
 * storyline resolution, character redemption arcs, and organizational authority. Proper
 * reinstatement procedures ensure that disciplinary actions have meaningful consequences
 * while providing clear pathways for roster members to return to good standing.
 *
 * COMMON SCENARIOS:
 * - Attempting to reinstate unemployed, released, or retired individuals who lack status
 * - Trying to reinstate members who are not currently suspended or under discipline
 * - Reinstatement conflicts with ongoing medical issues, injuries, or recovery protocols
 * - Missing suspension prerequisites or incomplete disciplinary resolution requirements
 * - Premature reinstatement attempts before required disciplinary periods have elapsed
 * - Administrative errors involving personnel not eligible for reinstatement status
 *
 * BUSINESS IMPACT:
 * - Maintains disciplinary procedure integrity and consistency across all roster operations
 * - Protects comeback storylines and character redemption arc narrative investments
 * - Ensures proper suspension resolution and administrative closure of disciplinary cases
 * - Prevents premature reinstatements that could undermine organizational authority
 * - Supports accurate disciplinary record keeping and policy enforcement tracking
 * - Maintains credibility with fans regarding the consequences of character actions
 */
final class CannotBeReinstatedException extends BaseBusinessException
{
    /**
     * Roster member is unemployed and cannot be reinstated.
     *
     * @param  Employable  $entity  The roster member that cannot be reinstated
     */
    public static function unemployed(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} is unemployed and cannot be reinstated.");
    }

    /**
     * Roster member is released and cannot be reinstated.
     *
     * @param  Employable  $entity  The roster member that cannot be reinstated
     */
    public static function released(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} is released and cannot be reinstated.");
    }

    /**
     * Roster member is permanently retired and cannot be reinstated.
     *
     * @param  Employable  $entity  The roster member that cannot be reinstated
     */
    public static function retired(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} is retired and cannot be reinstated.");
    }

    /**
     * Roster member has future employment and cannot be reinstated before employment begins.
     *
     * @param  Employable  $entity  The roster member that cannot be reinstated
     */
    public static function hasFutureEmployment(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} has not been officially employed and cannot be reinstated.");
    }

    /**
     * Roster member is already bookable and does not need reinstatement.
     *
     * @param  Employable  $entity  The roster member that cannot be reinstated
     */
    public static function bookable(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} is already bookable and does not need reinstatement.");
    }

    /**
     * Roster member is injured and cannot be reinstated until medical clearance.
     *
     * @param  Employable  $entity  The roster member that cannot be reinstated
     * @param  string|null  $injuryDetails  Optional details about the injury
     */
    public static function injured(Employable $entity, ?string $injuryDetails = null): static
    {
        $context = self::formatModelContext($entity);
        $injury = $injuryDetails ? " ({$injuryDetails})" : '';

        return new static("{$context} is injured{$injury} and cannot be reinstated until medically cleared.");
    }

    /**
     * Roster member is already available and does not need reinstatement.
     *
     * @param  Employable  $entity  The roster member that cannot be reinstated
     */
    public static function available(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} is already available and does not need reinstatement.");
    }

    /**
     * Roster member cannot be reinstated due to unresolved suspension conditions.
     *
     * @param  Employable  $entity  The roster member that cannot be reinstated
     * @param  string  $unresolvedCondition  Description of the unresolved condition
     */
    public static function unresolvedSuspensionCondition(Employable $entity, string $unresolvedCondition): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be reinstated due to unresolved suspension condition: {$unresolvedCondition}.");
    }

    /**
     * Roster member cannot be reinstated due to pending disciplinary review.
     *
     * @param  Employable  $entity  The roster member that cannot be reinstated
     * @param  string|null  $reviewDetails  Optional details about the pending review
     */
    public static function pendingDisciplinaryReview(Employable $entity, ?string $reviewDetails = null): static
    {
        $context = self::formatModelContext($entity);
        $details = $reviewDetails ? " ({$reviewDetails})" : '';

        return new static("{$context} cannot be reinstated while disciplinary review is pending{$details}.");
    }

    /**
     * Roster member cannot be reinstated due to incomplete rehabilitation program.
     *
     * @param  Employable  $entity  The roster member that cannot be reinstated
     * @param  string  $rehabilitationProgram  Description of the incomplete rehabilitation program
     */
    public static function incompleteRehabilitation(Employable $entity, string $rehabilitationProgram): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be reinstated until completing rehabilitation program: {$rehabilitationProgram}.");
    }

    /**
     * Roster member cannot be reinstated due to minimum suspension period not being met.
     *
     * @param  Employable  $entity  The roster member that cannot be reinstated
     * @param  int  $minimumDays  Minimum required suspension days
     * @param  int  $currentDays  Current days suspended
     */
    public static function minimumSuspensionPeriod(Employable $entity, int $minimumDays, int $currentDays): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be reinstated: requires {$minimumDays} days suspension but only {$currentDays} days have passed.");
    }

    /**
     * Roster member cannot be reinstated without proper authorization.
     *
     * @param  Employable  $entity  The roster member that cannot be reinstated
     * @param  string  $authorizationLevel  Required authorization level for reinstatement
     */
    public static function unauthorizedReinstatement(Employable $entity, string $authorizationLevel): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be reinstated without {$authorizationLevel} authorization.");
    }

    /**
     * Roster member cannot be reinstated due to ongoing contractual disputes.
     *
     * @param  Employable  $entity  The roster member that cannot be reinstated
     * @param  string  $disputeDetails  Description of the contractual dispute
     */
    public static function contractualDispute(Employable $entity, string $disputeDetails): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be reinstated due to ongoing contractual dispute: {$disputeDetails}.");
    }
}
