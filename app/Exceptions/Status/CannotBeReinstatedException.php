<?php

declare(strict_types=1);

namespace App\Exceptions\Status;

use App\Exceptions\BaseBusinessException;

/**
 * Exception thrown when an entity cannot be reinstated due to business rule violations.
 *
 * This exception handles scenarios where reinstatement is prevented by current state
 * or business logic constraints in wrestling promotion disciplinary management.
 *
 * BUSINESS CONTEXT:
 * Reinstatement represents the restoration of an entity from suspended status back
 * to active competition eligibility. Failed reinstatements can disrupt disciplinary
 * procedures, storyline resolutions, and comeback narratives.
 *
 * COMMON SCENARIOS:
 * - Attempting to reinstate unemployed, released, or retired individuals
 * - Trying to reinstate entities that are not currently suspended
 * - Reinstatement conflicts with ongoing medical or disciplinary issues
 * - Missing suspension prerequisites for proper reinstatement workflow
 *
 * BUSINESS IMPACT:
 * - Maintains disciplinary procedure integrity and consistency
 * - Protects comeback storylines and character redemption arcs
 * - Ensures proper suspension resolution and administrative closure
 * - Prevents premature reinstatements that could undermine authority
 */
class CannotBeReinstatedException extends BaseBusinessException
{
    /**
     * Entity is unemployed and cannot be reinstated.
     *
     * @param  string|null  $entityType  Optional entity type for context (e.g., 'wrestler', 'referee')
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function unemployed(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is unemployed and cannot be reinstated.");
    }

    /**
     * Entity is released and cannot be reinstated.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function released(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is released and cannot be reinstated.");
    }

    /**
     * Entity is permanently retired and cannot be reinstated.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function retired(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is retired and cannot be reinstated.");
    }

    /**
     * Entity has future employment and cannot be reinstated before employment begins.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function hasFutureEmployment(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} has not been officially employed and cannot be reinstated.");
    }

    /**
     * Entity is already bookable and does not need reinstatement.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function bookable(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is already bookable and does not need reinstatement.");
    }

    /**
     * Entity is injured and cannot be reinstated until medical clearance.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     * @param  string|null  $injuryDetails  Optional details about the injury
     */
    public static function injured(?string $entityType = null, ?string $entityName = null, ?string $injuryDetails = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';
        $injury = $injuryDetails ? " ({$injuryDetails})" : '';

        return new self("This{$context} is injured{$injury} and cannot be reinstated until medically cleared.");
    }

    /**
     * Entity is already available and does not need reinstatement.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function available(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} is already available and does not need reinstatement.");
    }

    /**
     * Entity cannot be reinstated due to unresolved suspension conditions.
     *
     * @param  string  $unresolvedCondition  Description of the unresolved condition
     * @param  string|null  $entityType  Optional entity type for context
     */
    public static function unresolvedSuspensionCondition(string $unresolvedCondition, ?string $entityType = null): static
    {
        $context = $entityType ? " {$entityType}" : ' entity';

        return new self("This{$context} cannot be reinstated due to unresolved suspension condition: {$unresolvedCondition}.");
    }

    /**
     * Entity cannot be reinstated due to pending disciplinary review.
     *
     * @param  string|null  $entityType  Optional entity type for context
     * @param  string|null  $entityName  Optional entity name for specific error messaging
     */
    public static function pendingDisciplinaryReview(?string $entityType = null, ?string $entityName = null): static
    {
        $context = $entityType && $entityName ? " {$entityType} '{$entityName}'" : ' entity';

        return new self("This{$context} cannot be reinstated while disciplinary review is pending.");
    }
}
