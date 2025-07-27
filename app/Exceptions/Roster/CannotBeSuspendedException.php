<?php

declare(strict_types=1);

namespace App\Exceptions\Roster;

use App\Exceptions\BaseBusinessException;
use App\Models\Contracts\Employable;

/**
 * Exception thrown when a roster member cannot be suspended due to business rule violations.
 *
 * This exception handles scenarios where suspension is prevented by current state
 * or business logic constraints in wrestling promotion disciplinary management.
 *
 * BUSINESS CONTEXT:
 * Suspension represents disciplinary action that temporarily removes roster members from
 * active competition while maintaining their employment status and benefits. This is a
 * critical tool for storyline development, character consequences, and organizational
 * authority. Suspensions must be applied consistently and fairly to maintain credibility
 * with both talent and audiences while serving legitimate business and creative purposes.
 *
 * COMMON SCENARIOS:
 * - Attempting to suspend unemployed, retired, or released individuals who lack active status
 * - Trying to suspend already suspended, injured, or unavailable personnel
 * - Tag team suspension conflicts due to individual member status complications
 * - Missing employment prerequisites required for proper disciplinary procedures
 * - Administrative errors involving personnel not eligible for suspension status
 * - Suspension conflicts with active championship reigns or major storyline commitments
 *
 * BUSINESS IMPACT:
 * - Maintains disciplinary procedure integrity and ensures consistent regulatory compliance
 * - Protects employment status accuracy and payroll calculation systems
 * - Ensures proper storyline consequences that enhance character development and fan engagement
 * - Prevents administrative errors that could affect union relations and labor agreements
 * - Supports accurate disciplinary record keeping for performance and legal documentation
 * - Maintains organizational authority and credibility in both storyline and backstage contexts
 */
final class CannotBeSuspendedException extends BaseBusinessException
{
    /**
     * Roster member is unemployed and cannot be suspended.
     *
     * @param  Employable  $entity  The roster member that cannot be suspended
     */
    public static function unemployed(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} is unemployed and cannot be suspended.");
    }

    /**
     * Roster member has future employment and cannot be suspended before official employment begins.
     *
     * @param  Employable  $entity  The roster member that cannot be suspended
     */
    public static function hasFutureEmployment(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} has not been officially employed and cannot be suspended.");
    }

    /**
     * Roster member is permanently retired and cannot be suspended.
     *
     * @param  Employable  $entity  The roster member that cannot be suspended
     */
    public static function retired(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} is retired and cannot be suspended.");
    }

    /**
     * Roster member is released and cannot be suspended.
     *
     * @param  Employable  $entity  The roster member that cannot be suspended
     */
    public static function released(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} is released and cannot be suspended.");
    }

    /**
     * Roster member is already suspended and cannot be suspended again.
     *
     * @param  Employable  $entity  The roster member that cannot be suspended
     * @param  string|null  $currentSuspensionReason  Optional reason for current suspension
     */
    public static function suspended(Employable $entity, ?string $currentSuspensionReason = null): static
    {
        $context = self::formatModelContext($entity);
        $reason = $currentSuspensionReason ? " ({$currentSuspensionReason})" : '';

        return new static("{$context} is already suspended{$reason}.");
    }

    /**
     * Roster member is injured and cannot be suspended while recovering.
     *
     * @param  Employable  $entity  The roster member that cannot be suspended
     * @param  string|null  $injuryDetails  Optional details about the injury
     */
    public static function injured(Employable $entity, ?string $injuryDetails = null): static
    {
        $context = self::formatModelContext($entity);
        $injury = $injuryDetails ? " ({$injuryDetails})" : '';

        return new static("{$context} is injured{$injury} and cannot be suspended.");
    }

    /**
     * Roster member cannot be suspended due to active championship reign.
     *
     * @param  Employable  $entity  The roster member that cannot be suspended
     * @param  string  $championshipTitle  Name of the championship currently held
     */
    public static function activeChampion(Employable $entity, string $championshipTitle): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} holds the {$championshipTitle} and cannot be suspended during their reign.");
    }

    /**
     * Roster member cannot be suspended due to contractual protection clauses.
     *
     * @param  Employable  $entity  The roster member that cannot be suspended
     * @param  string  $protectionClause  Description of the contractual protection
     */
    public static function contractualProtection(Employable $entity, string $protectionClause): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be suspended due to contractual protection: {$protectionClause}.");
    }

    /**
     * Roster member cannot be suspended without proper authorization.
     *
     * @param  Employable  $entity  The roster member that cannot be suspended
     * @param  string  $authorizationLevel  Required authorization level for suspension
     */
    public static function unauthorizedSuspension(Employable $entity, string $authorizationLevel): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be suspended without {$authorizationLevel} authorization.");
    }

    /**
     * Roster member cannot be suspended due to pending legal proceedings.
     *
     * @param  Employable  $entity  The roster member that cannot be suspended
     * @param  string  $legalProceedingDetails  Details about the pending legal proceedings
     */
    public static function pendingLegalProceedings(Employable $entity, string $legalProceedingDetails): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be suspended during pending legal proceedings: {$legalProceedingDetails}.");
    }

    /**
     * Roster member cannot be suspended due to union protection.
     *
     * @param  Employable  $entity  The roster member that cannot be suspended
     * @param  string  $unionProtectionDetails  Details about the union protection
     */
    public static function unionProtection(Employable $entity, string $unionProtectionDetails): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be suspended due to union protection: {$unionProtectionDetails}.");
    }

    /**
     * Roster member cannot be suspended during probationary employment period.
     *
     * @param  Employable  $entity  The roster member that cannot be suspended
     * @param  int  $probationaryDaysRemaining  Days remaining in probationary period
     */
    public static function probationaryPeriod(Employable $entity, int $probationaryDaysRemaining): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be suspended during probationary period ({$probationaryDaysRemaining} days remaining).");
    }

    /**
     * Roster member cannot be suspended due to active storyline requirements.
     *
     * @param  Employable  $entity  The roster member that cannot be suspended
     * @param  string  $storylineDetails  Description of the active storyline requirement
     */
    public static function criticalStorylineRole(Employable $entity, string $storylineDetails): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be suspended due to critical storyline role: {$storylineDetails}.");
    }
}
