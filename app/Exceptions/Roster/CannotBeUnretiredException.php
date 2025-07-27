<?php

declare(strict_types=1);

namespace App\Exceptions\Roster;

use App\Exceptions\BaseBusinessException;
use App\Models\Contracts\Employable;
use App\Models\Stables\Stable;

/**
 * Exception thrown when a roster member cannot be unretired due to business rule violations.
 *
 * This exception handles scenarios where unretirement is prevented by current state
 * or business logic constraints in wrestling promotion comeback management.
 *
 * BUSINESS CONTEXT:
 * Unretirement represents bringing a retired roster member back to active competition,
 * often for special occasions, comebacks, or storyline purposes. Failed unretirements
 * can disrupt comeback narratives and special event planning.
 *
 * COMMON SCENARIOS:
 * - Attempting to unretire roster members that are not currently retired
 * - Trying to unretire members with permanent retirement status
 * - Unretirement conflicts with age restrictions or medical clearances
 * - Missing prerequisites for proper comeback workflow approval
 *
 * BUSINESS IMPACT:
 * - Maintains retirement status integrity and career timeline accuracy
 * - Protects comeback storylines and special event marketing value
 * - Ensures proper medical clearances for returning performers
 * - Prevents unauthorized comebacks that could devalue retirement ceremonies
 */
final class CannotBeUnretiredException extends BaseBusinessException
{
    /**
     * Roster member is not currently retired and cannot be unretired.
     *
     * @param  Employable|Stable  $entity  The roster member that cannot be unretired
     */
    public static function notRetired(Employable|Stable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} is not currently retired and cannot be unretired.");
    }

    /**
     * Roster member has permanent retirement status and cannot be unretired.
     *
     * @param  Employable|Stable  $entity  The roster member that cannot be unretired
     */
    public static function permanentRetirement(Employable|Stable $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} has permanent retirement status and cannot be unretired.");
    }

    /**
     * Roster member cannot be unretired due to medical restrictions.
     *
     * @param  Employable  $entity  The roster member that cannot be unretired
     * @param  string  $medicalRestriction  Description of the medical restriction
     */
    public static function medicalRestriction(Employable $entity, string $medicalRestriction): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} has medical restriction ({$medicalRestriction}) and cannot be unretired.");
    }

    /**
     * Roster member cannot be unretired without proper authorization.
     *
     * @param  Employable|Stable  $entity  The roster member that cannot be unretired
     * @param  string  $authorizationLevel  Required authorization level for unretirement
     */
    public static function unauthorizedUnretirement(Employable|Stable $entity, string $authorizationLevel): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} cannot be unretired without {$authorizationLevel} authorization.");
    }

    /**
     * Roster member cannot be unretired due to contractual limitations.
     *
     * @param  Employable|Stable  $entity  The roster member that cannot be unretired
     * @param  string  $contractualLimitation  Description of the contractual limitation
     */
    public static function contractualLimitation(Employable|Stable $entity, string $contractualLimitation): static
    {
        $context = self::formatModelContext($entity);

        return new self("{$context} has contractual limitation ({$contractualLimitation}) and cannot be unretired.");
    }
}
