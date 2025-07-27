<?php

declare(strict_types=1);

namespace App\Exceptions\Roster;

use App\Exceptions\BaseBusinessException;
use App\Models\Contracts\Employable;

/**
 * Exception thrown when a roster member cannot be injured due to business rule violations.
 *
 * This exception handles scenarios where injury assignment is prevented by current state
 * or business logic constraints in wrestling promotion safety management.
 *
 * BUSINESS CONTEXT:
 * Injury assignment represents the formal recognition and recording of physical conditions
 * that affect roster member availability and safety protocols. This is critical for
 * maintaining accurate medical records, insurance compliance, and ensuring proper
 * medical care and recovery protocols are followed throughout the organization.
 *
 * COMMON SCENARIOS:
 * - Attempting to injure unemployed, released, or retired individuals who lack coverage
 * - Trying to injure already injured, suspended, or unavailable personnel
 * - Injury conflicts with employment status or contractual medical obligations
 * - Missing employment prerequisites required for proper medical coverage and care
 * - Attempts to assign injuries during inactive employment periods or contract gaps
 * - Administrative errors involving personnel not eligible for injury status
 *
 * BUSINESS IMPACT:
 * - Maintains medical record accuracy and ensures proper insurance claim processing
 * - Protects worker safety protocols and organizational liability management
 * - Ensures proper storyline injury integration with realistic recovery timelines
 * - Prevents administrative errors that could affect medical benefits and coverage
 * - Supports accurate injury tracking for safety analysis and prevention programs
 * - Maintains compliance with athletic commission and regulatory medical requirements
 */
final class CannotBeInjuredException extends BaseBusinessException
{
    /**
     * Roster member is unemployed and cannot be injured.
     *
     * @param  Employable  $entity  The roster member that cannot be injured
     */
    public static function unemployed(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} is unemployed and cannot be injured.");
    }

    /**
     * Roster member is released and cannot be injured.
     *
     * @param  Employable  $entity  The roster member that cannot be injured
     */
    public static function released(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} is released and cannot be injured.");
    }

    /**
     * Roster member is permanently retired and cannot be injured.
     *
     * @param  Employable  $entity  The roster member that cannot be injured
     */
    public static function retired(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} is retired and cannot be injured.");
    }

    /**
     * Roster member has future employment and cannot be injured before employment begins.
     *
     * @param  Employable  $entity  The roster member that cannot be injured
     */
    public static function hasFutureEmployment(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} has not been officially employed and cannot be injured.");
    }

    /**
     * Roster member is already injured and cannot be injured again.
     *
     * @param  Employable  $entity  The roster member that cannot be injured
     * @param  string|null  $currentInjury  Optional description of current injury
     */
    public static function injured(Employable $entity, ?string $currentInjury = null): static
    {
        $context = self::formatModelContext($entity);
        $injury = $currentInjury ? " ({$currentInjury})" : '';

        return new static("{$context} is already currently injured{$injury}.");
    }

    /**
     * Roster member is suspended and cannot be injured while under suspension.
     *
     * @param  Employable  $entity  The roster member that cannot be injured
     * @param  string|null  $suspensionReason  Optional reason for suspension
     */
    public static function suspended(Employable $entity, ?string $suspensionReason = null): static
    {
        $context = self::formatModelContext($entity);
        $reason = $suspensionReason ? " ({$suspensionReason})" : '';

        return new static("{$context} is suspended{$reason} and cannot be injured.");
    }

    /**
     * Roster member cannot be injured due to insufficient medical coverage.
     *
     * @param  Employable  $entity  The roster member that cannot be injured
     * @param  string  $coverageIssue  Description of the medical coverage issue
     */
    public static function insufficientMedicalCoverage(Employable $entity, string $coverageIssue): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be injured due to medical coverage issue: {$coverageIssue}.");
    }

    /**
     * Roster member cannot be injured due to contractual restrictions.
     *
     * @param  Employable  $entity  The roster member that cannot be injured
     * @param  string  $contractualRestriction  Description of the contractual restriction
     */
    public static function contractualRestriction(Employable $entity, string $contractualRestriction): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be injured due to contractual restriction: {$contractualRestriction}.");
    }

    /**
     * Roster member cannot be injured during their probationary employment period.
     *
     * @param  Employable  $entity  The roster member that cannot be injured
     * @param  int  $probationaryDaysRemaining  Days remaining in probationary period
     */
    public static function probationaryPeriod(Employable $entity, int $probationaryDaysRemaining): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be injured during probationary period ({$probationaryDaysRemaining} days remaining).");
    }

    /**
     * Roster member cannot be injured due to pending medical examination.
     *
     * @param  Employable  $entity  The roster member that cannot be injured
     * @param  string  $examinationDetails  Details about the pending examination
     */
    public static function pendingMedicalExamination(Employable $entity, string $examinationDetails): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be injured while pending medical examination: {$examinationDetails}.");
    }
}
