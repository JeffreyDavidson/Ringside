<?php

declare(strict_types=1);

namespace App\Exceptions\Status;

use App\Exceptions\BaseBusinessException;

/**
 * Exception thrown when a stable cannot be disbanded due to business rule violations.
 *
 * This exception handles scenarios where stable disbandment is prevented by current state
 * or business logic constraints in wrestling promotion stable management.
 *
 * BUSINESS CONTEXT:
 * Stable disbandment represents the formal dissolution of a wrestling faction,
 * affecting storylines, member relationships, and ongoing feuds. Failed disbandments
 * can disrupt narrative continuity and member career trajectories.
 *
 * COMMON SCENARIOS:
 * - Attempting to disband an inactive or already disbanded stable
 * - Trying to disband stables that are permanently retired
 * - Disbandment conflicts with active storylines or contractual obligations
 * - Missing prerequisites for proper disbandment workflow
 *
 * BUSINESS IMPACT:
 * - Maintains stable lifecycle integrity and member relationship consistency
 * - Protects ongoing storylines and narrative investment
 * - Ensures proper member release and reallocation procedures
 * - Prevents disruption of active feuds and championship pursuits
 */
class CannotBeDisbandedException extends BaseBusinessException
{
    /**
     * Stable is not currently active and cannot be disbanded.
     *
     * @param  string|null  $stableName  Optional stable name for specific error messaging
     */
    public static function unactivated(?string $stableName = null): static
    {
        $context = $stableName ? " stable '{$stableName}'" : ' stable';

        return new self("This{$context} is not active and cannot be disbanded.");
    }

    /**
     * Stable is already disbanded and cannot be disbanded again.
     *
     * @param  string|null  $stableName  Optional stable name for specific error messaging
     */
    public static function disbanded(?string $stableName = null): static
    {
        $context = $stableName ? " stable '{$stableName}'" : ' stable';

        return new self("This{$context} is already disbanded.");
    }

    /**
     * Stable is permanently retired and cannot be disbanded.
     *
     * @param  string|null  $stableName  Optional stable name for specific error messaging
     */
    public static function retired(?string $stableName = null): static
    {
        $context = $stableName ? " stable '{$stableName}'" : ' stable';

        return new self("This{$context} is retired and cannot be disbanded.");
    }

    /**
     * Stable has not been officially activated and cannot be disbanded.
     *
     * @param  string|null  $stableName  Optional stable name for specific error messaging
     */
    public static function hasFutureActivation(?string $stableName = null): static
    {
        $context = $stableName ? " stable '{$stableName}'" : ' stable';

        return new self("This{$context} has not been officially activated and cannot be disbanded.");
    }

    /**
     * Stable cannot be disbanded due to active storyline commitments.
     *
     * @param  string  $storylineDetails  Description of the active storyline preventing disbandment
     * @param  string|null  $stableName  Optional stable name for specific error messaging
     */
    public static function activeStoryline(string $storylineDetails, ?string $stableName = null): static
    {
        $context = $stableName ? " stable '{$stableName}'" : ' stable';

        return new self("This{$context} cannot be disbanded due to active storyline: {$storylineDetails}.");
    }

    /**
     * Stable cannot be disbanded while members have active championship reigns.
     *
     * @param  array<string>  $championshipTitles  List of championship titles held by members
     * @param  string|null  $stableName  Optional stable name for specific error messaging
     */
    public static function membersHoldingTitles(array $championshipTitles, ?string $stableName = null): static
    {
        $context = $stableName ? " stable '{$stableName}'" : ' stable';
        $titles = implode(', ', $championshipTitles);

        return new self("This{$context} cannot be disbanded while members hold championships: {$titles}.");
    }
}
