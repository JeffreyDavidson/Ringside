<?php

declare(strict_types=1);

namespace App\Exceptions\Roster;

use App\Exceptions\BaseBusinessException;
use App\Models\Contracts\Employable;

/**
 * Exception thrown when a roster member cannot be employed due to business rule violations.
 *
 * This exception handles scenarios where employment is prevented by current state
 * or business logic constraints in wrestling promotion roster management.
 *
 * BUSINESS CONTEXT:
 * Employment represents the active contractual relationship between the promotion
 * and wrestlers, referees, managers, or tag teams. Failed employment can disrupt
 * roster management, event planning, and contractual obligations.
 *
 * COMMON SCENARIOS:
 * - Attempting to employ an already employed roster member
 * - Trying to employ retired or suspended individuals
 * - Employment conflicts with existing contracts or obligations
 * - Missing contractual prerequisites for employment
 *
 * BUSINESS IMPACT:
 * - Prevents double employment and contractual conflicts
 * - Maintains roster integrity and employment status accuracy
 * - Protects payroll calculations and appearance fee management
 * - Ensures proper regulatory compliance and union obligations
 */
final class CannotBeEmployedException extends BaseBusinessException
{
    /**
     * Roster member is already employed and cannot be re-employed.
     *
     * @param  Employable  $entity  The roster member that cannot be employed
     */
    public static function employed(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} is already employed and cannot be re-employed.");
    }

    /**
     * Roster member cannot be employed because they are permanently retired.
     *
     * @param  Employable  $entity  The roster member that cannot be employed
     */
    public static function retired(Employable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} is retired and cannot be employed.");
    }

    /**
     * Roster member cannot be employed while currently suspended.
     *
     * @param  Employable  $entity  The roster member that cannot be employed
     * @param  string|null  $suspensionReason  Optional reason for suspension
     */
    public static function suspended(Employable $entity, ?string $suspensionReason = null): static
    {
        $context = self::formatModelContext($entity);
        $reason = $suspensionReason ? " ({$suspensionReason})" : '';

        return new static("{$context} is currently suspended{$reason} and cannot be employed.");
    }

    /**
     * Roster member cannot be employed due to contractual conflicts.
     *
     * @param  Employable  $entity  The roster member that cannot be employed
     * @param  string  $conflict  Description of the contractual conflict
     */
    public static function contractualConflict(Employable $entity, string $conflict): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} has contractual conflict ({$conflict}) and cannot be employed.");
    }

    /**
     * Roster member cannot be employed due to missing qualifications or requirements.
     *
     * @param  Employable  $entity  The roster member that cannot be employed
     * @param  string  $requirement  Description of the missing requirement
     */
    public static function missingRequirement(Employable $entity, string $requirement): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be employed due to missing requirement: {$requirement}.");
    }

    /**
     * Roster member cannot be employed due to medical clearance issues.
     *
     * @param  Employable  $entity  The roster member that cannot be employed
     * @param  string  $medicalIssue  Description of the medical clearance issue
     */
    public static function medicalClearance(Employable $entity, string $medicalIssue): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} lacks medical clearance ({$medicalIssue}) and cannot be employed.");
    }

    /**
     * Roster member cannot be employed without proper authorization.
     *
     * @param  Employable  $entity  The roster member that cannot be employed
     * @param  string  $authorizationLevel  Required authorization level for employment
     */
    public static function unauthorizedEmployment(Employable $entity, string $authorizationLevel): static
    {
        $context = self::formatModelContext($entity);

        return new static("{$context} cannot be employed without {$authorizationLevel} authorization.");
    }
}
