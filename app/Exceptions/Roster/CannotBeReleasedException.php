<?php

declare(strict_types=1);

namespace App\Exceptions\Roster;

use App\Exceptions\BaseBusinessException;
use App\Models\Contracts\Employable;

/**
 * Exception thrown when a roster member cannot be released due to business rule violations.
 *
 * This exception handles scenarios where release is prevented by current state
 * or business logic constraints in wrestling promotion contract management.
 *
 * BUSINESS CONTEXT:
 * Release represents the formal termination of employment contracts while maintaining
 * the possibility of future re-employment. This is distinct from retirement (permanent)
 * and differs from suspension (temporary discipline). Releases affect payroll calculations,
 * storyline conclusions, and roster planning while preserving future booking options
 * and maintaining professional relationships within the industry.
 *
 * COMMON SCENARIOS:
 * - Attempting to release unemployed, already released, or retired individuals
 * - Trying to release members with future employment commitments or contractual obligations
 * - Release conflicts with active championship reigns, storylines, or ongoing feuds
 * - Missing employment prerequisites for proper contract termination procedures
 * - Premature release attempts during notice periods or contractual cooling-off periods
 * - Administrative errors involving personnel not eligible for standard release procedures
 *
 * BUSINESS IMPACT:
 * - Maintains contract integrity and employment status accuracy across roster management
 * - Protects payroll calculations, benefit administration, and financial planning
 * - Ensures proper notice periods, severance compliance, and regulatory requirements
 * - Prevents premature releases that could affect ongoing storylines and booking commitments
 * - Supports accurate employment record keeping and industry relationship management
 * - Maintains flexibility for future re-employment and talent relationship preservation
 */
final class CannotBeReleasedException extends BaseBusinessException
{
    /**
     * Roster member is unemployed and cannot be released.
     *
     * @param  Employable  $entity  The roster member that cannot be released
     */
    public static function unemployed(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} is unemployed and cannot be released.");
    }

    /**
     * Roster member is already released and cannot be released again.
     *
     * @param  Employable  $entity  The roster member that cannot be released
     */
    public static function released(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} is already released.");
    }

    /**
     * Roster member is permanently retired and cannot be released.
     *
     * @param  Employable  $entity  The roster member that cannot be released
     */
    public static function retired(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} is retired and cannot be released.");
    }

    /**
     * Roster member has future employment and cannot be released before employment begins.
     *
     * @param  Employable  $entity  The roster member that cannot be released
     */
    public static function hasFutureEmployment(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} has not been officially employed and cannot be released.");
    }

    /**
     * Roster member cannot be released while holding active championship titles.
     *
     * @param  Employable  $entity  The roster member that cannot be released
     * @param  array<string>  $championshipTitles  List of championship titles currently held
     */
    public static function activeChampion(Employable $entity, array $championshipTitles): static
    {
        $context = self::formatModelContext($entity);
        $titles = implode(', ', $championshipTitles);

        return new static("{$context} holds active championships ({$titles}) and cannot be released until titles are vacated.");
    }

    /**
     * Roster member cannot be released due to contractual notice period requirements.
     *
     * @param  Employable  $entity  The roster member that cannot be released
     * @param  int  $noticePeriodDays  Number of days required for notice period
     * @param  int  $remainingDays  Days remaining in notice period
     */
    public static function noticePeriodRequired(Employable $entity, int $noticePeriodDays, int $remainingDays): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} requires {$noticePeriodDays} days notice: {$remainingDays} days remaining before release can be processed.");
    }

    /**
     * Roster member cannot be released due to active storyline commitments.
     *
     * @param  Employable  $entity  The roster member that cannot be released
     * @param  string  $storylineDetails  Description of the active storyline
     */
    public static function activeStoryline(Employable $entity, string $storylineDetails): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be released due to active storyline commitment: {$storylineDetails}.");
    }

    /**
     * Roster member cannot be released due to upcoming contractual obligations.
     *
     * @param  Employable  $entity  The roster member that cannot be released
     * @param  string  $upcomingObligations  Description of upcoming obligations
     */
    public static function upcomingObligations(Employable $entity, string $upcomingObligations): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be released due to upcoming contractual obligations: {$upcomingObligations}.");
    }

    /**
     * Roster member cannot be released without proper authorization.
     *
     * @param  Employable  $entity  The roster member that cannot be released
     * @param  string  $authorizationLevel  Required authorization level for release
     */
    public static function unauthorizedRelease(Employable $entity, string $authorizationLevel): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be released without {$authorizationLevel} authorization.");
    }

    /**
     * Roster member cannot be released due to ongoing disciplinary proceedings.
     *
     * @param  Employable  $entity  The roster member that cannot be released
     * @param  string  $disciplinaryDetails  Details about the ongoing disciplinary proceedings
     */
    public static function ongoingDisciplinaryProceedings(Employable $entity, string $disciplinaryDetails): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be released during ongoing disciplinary proceedings: {$disciplinaryDetails}.");
    }

    /**
     * Roster member cannot be released due to union or regulatory restrictions.
     *
     * @param  Employable  $entity  The roster member that cannot be released
     * @param  string  $restrictionDetails  Details about the union or regulatory restriction
     */
    public static function unionRestriction(Employable $entity, string $restrictionDetails): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be released due to union restriction: {$restrictionDetails}.");
    }

    /**
     * Roster member cannot be released due to pending medical evaluation.
     *
     * @param  Employable  $entity  The roster member that cannot be released
     * @param  string  $evaluationDetails  Details about the pending medical evaluation
     */
    public static function pendingMedicalEvaluation(Employable $entity, string $evaluationDetails): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be released while pending medical evaluation: {$evaluationDetails}.");
    }
}
