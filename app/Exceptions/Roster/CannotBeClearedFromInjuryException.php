<?php

declare(strict_types=1);

namespace App\Exceptions\Roster;

use App\Exceptions\BaseBusinessException;
use App\Models\Contracts\Employable;

/**
 * Exception thrown when a roster member cannot be cleared from injury due to business rule violations.
 *
 * This exception handles scenarios where injury clearance is prevented by current state
 * or medical protocol constraints in wrestling promotion safety management.
 *
 * BUSINESS CONTEXT:
 * Injury clearance represents the critical medical approval process for roster members
 * to return to active competition after injury recovery. This process is fundamental
 * to performer safety, insurance compliance, and maintaining professional wrestling's
 * credibility regarding athlete welfare and medical protocols.
 *
 * COMMON SCENARIOS:
 * - Attempting to clear members who are not currently on injury status
 * - Trying to clear injuries without proper medical documentation or approval
 * - Clearance conflicts with ongoing medical treatment requirements or recovery protocols
 * - Missing medical prerequisites such as doctor clearance, physical therapy completion
 * - Premature clearance attempts before minimum recovery periods have elapsed
 * - Clearance blocked by insurance requirements or regulatory compliance issues
 *
 * BUSINESS IMPACT:
 * - Maintains performer safety and medical protocol integrity across all roster operations
 * - Protects against premature returns that could worsen injuries or create liability
 * - Ensures proper insurance compliance and reduces organizational legal exposure
 * - Supports accurate medical record keeping and injury recovery tracking systems
 * - Maintains credibility with athletic commissions and regulatory bodies
 * - Protects storyline continuity by ensuring realistic injury recovery timelines
 */
final class CannotBeClearedFromInjuryException extends BaseBusinessException
{
    /**
     * Roster member is not currently injured and cannot be cleared from injury.
     *
     * @param  Employable  $entity  The roster member that cannot be cleared from injury
     */
    public static function notInjured(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} is not currently injured and cannot be cleared from injury.");
    }

    /**
     * Roster member cannot be cleared due to insufficient medical documentation.
     *
     * @param  Employable  $entity  The roster member that cannot be cleared from injury
     * @param  string  $missingDocumentation  Description of missing medical documentation
     */
    public static function insufficientMedicalDocumentation(Employable $entity, string $missingDocumentation): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be cleared from injury due to insufficient medical documentation: {$missingDocumentation}.");
    }

    /**
     * Roster member cannot be cleared due to ongoing medical treatment requirements.
     *
     * @param  Employable  $entity  The roster member that cannot be cleared from injury
     * @param  string  $ongoingTreatment  Description of ongoing medical treatment
     */
    public static function ongoingTreatment(Employable $entity, string $ongoingTreatment): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be cleared from injury due to ongoing treatment: {$ongoingTreatment}.");
    }

    /**
     * Roster member cannot be cleared without proper medical authorization.
     *
     * @param  Employable  $entity  The roster member that cannot be cleared from injury
     * @param  string  $authorizationLevel  Required authorization level for injury clearance
     */
    public static function unauthorizedClearance(Employable $entity, string $authorizationLevel): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be cleared from injury without {$authorizationLevel} authorization.");
    }

    /**
     * Roster member cannot be cleared due to minimum recovery period not being met.
     *
     * @param  Employable  $entity  The roster member that cannot be cleared from injury
     * @param  int  $minimumDays  Minimum required recovery days
     * @param  int  $currentDays  Current days since injury
     */
    public static function minimumRecoveryPeriod(Employable $entity, int $minimumDays, int $currentDays): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be cleared from injury: requires {$minimumDays} days recovery but only {$currentDays} days have passed.");
    }

    /**
     * Roster member cannot be cleared due to insurance compliance requirements.
     *
     * @param  Employable  $entity  The roster member that cannot be cleared from injury
     * @param  string  $insuranceRequirement  Description of the insurance requirement
     */
    public static function insuranceCompliance(Employable $entity, string $insuranceRequirement): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be cleared from injury due to insurance compliance requirement: {$insuranceRequirement}.");
    }

    /**
     * Roster member cannot be cleared due to incomplete rehabilitation program.
     *
     * @param  Employable  $entity  The roster member that cannot be cleared from injury
     * @param  string  $incompleteProgram  Description of the incomplete rehabilitation program
     */
    public static function incompleteRehabilitation(Employable $entity, string $incompleteProgram): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be cleared from injury until completing rehabilitation: {$incompleteProgram}.");
    }

    /**
     * Roster member cannot be cleared due to failed medical evaluation.
     *
     * @param  Employable  $entity  The roster member that cannot be cleared from injury
     * @param  string  $evaluationIssue  Description of the medical evaluation issue
     */
    public static function failedMedicalEvaluation(Employable $entity, string $evaluationIssue): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be cleared from injury due to failed medical evaluation: {$evaluationIssue}.");
    }
}
