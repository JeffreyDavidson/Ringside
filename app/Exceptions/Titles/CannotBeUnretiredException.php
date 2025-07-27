<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

/**
 * Exception thrown when a title cannot be unretired due to business rule violations.
 *
 * This exception handles scenarios where title unretirement is prevented by current state
 * or business logic constraints in wrestling promotion championship management.
 *
 * BUSINESS CONTEXT:
 * Title unretirement represents bringing permanently retired championships back to active competition,
 * typically for special occasions, legacy events, anniversary celebrations, or major storylines.
 * This differs from reinstatement (returning pulled titles) and reactivation (general status changes).
 *
 * COMMON SCENARIOS:
 * - Attempting to unretire non-retired or active championships
 * - Trying to unretire titles with permanent or protected retirement status
 * - Unretirement conflicts with current championship structures or successor titles
 * - Missing prerequisites for proper legacy comeback workflow and authorization
 * - Insufficient historical significance or anniversary timing for unretirement
 * - Conflicts with Hall of Fame status or historical preservation requirements
 *
 * BUSINESS IMPACT:
 * - Maintains retirement status integrity and championship timeline accuracy
 * - Protects the significance of retirement ceremonies and legacy moments
 * - Ensures proper championship lineage and historical documentation for returning titles
 * - Prevents unauthorized returns that could devalue retirement significance
 * - Preserves the special nature of championship comebacks and anniversary events
 */
final class CannotBeUnretiredException extends BaseBusinessException
{
    /**
     * Title is not currently retired and cannot be unretired.
     *
     * @param  Title  $title  The title that cannot be unretired
     */
    public static function notRetired(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} is not currently retired and cannot be unretired.");
    }

    /**
     * Title has permanent retirement status and cannot be unretired.
     *
     * @param  Title  $title  The title that cannot be unretired
     */
    public static function permanentRetirement(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} has permanent retirement status and cannot be unretired.");
    }

    /**
     * Title cannot be unretired without proper authorization.
     *
     * @param  Title  $title  The title that cannot be unretired
     * @param  string  $authorizationLevel  Required authorization level for unretirement
     */
    public static function unauthorizedUnretirement(Title $title, string $authorizationLevel): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be unretired without {$authorizationLevel} authorization.");
    }

    /**
     * Title cannot be unretired due to contractual limitations.
     *
     * @param  Title  $title  The title that cannot be unretired
     * @param  string  $contractualLimitation  Description of the contractual limitation
     */
    public static function contractualLimitation(Title $title, string $contractualLimitation): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be unretired due to contractual limitation: {$contractualLimitation}.");
    }

    /**
     * Title cannot be unretired due to championship structure conflicts.
     *
     * @param  Title  $title  The title that cannot be unretired
     * @param  string  $conflictDetails  Description of the championship structure conflict
     */
    public static function championshipStructureConflict(Title $title, string $conflictDetails): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be unretired due to championship structure conflict: {$conflictDetails}.");
    }

    /**
     * Title cannot be unretired due to existing similar championship.
     *
     * @param  Title  $title  The title that cannot be unretired
     * @param  string  $existingTitleName  Name of the existing similar championship
     */
    public static function similarChampionshipExists(Title $title, string $existingTitleName): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be unretired because similar championship '{$existingTitleName}' already exists.");
    }

    /**
     * Title cannot be unretired due to historical preservation requirements.
     *
     * @param  Title  $title  The title that cannot be unretired
     * @param  string  $preservationDetails  Description of the historical preservation requirements
     */
    public static function historicalPreservation(Title $title, string $preservationDetails): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be unretired due to historical preservation requirements: {$preservationDetails}.");
    }

    /**
     * Title cannot be unretired due to Hall of Fame status restrictions.
     *
     * @param  Title  $title  The title that cannot be unretired
     * @param  string  $hallOfFameDetails  Description of the Hall of Fame restriction
     */
    public static function hallOfFameRestriction(Title $title, string $hallOfFameDetails): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be unretired due to Hall of Fame restriction: {$hallOfFameDetails}.");
    }

    /**
     * Title cannot be unretired due to successor championship obligations.
     *
     * @param  Title  $title  The title that cannot be unretired
     * @param  string  $successorTitle  Name of the successor championship
     */
    public static function successorChampionshipConflict(Title $title, string $successorTitle): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be unretired due to conflict with successor championship '{$successorTitle}'.");
    }

    /**
     * Title cannot be unretired due to insufficient legacy significance.
     *
     * @param  Title  $title  The title that cannot be unretired
     * @param  string  $significanceRequirement  Description of the required significance level
     */
    public static function insufficientLegacySignificance(Title $title, string $significanceRequirement): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be unretired due to insufficient legacy significance: {$significanceRequirement}.");
    }

    /**
     * Title cannot be unretired outside of appropriate anniversary timing.
     *
     * @param  Title  $title  The title that cannot be unretired
     * @param  string  $anniversaryRequirement  Description of the anniversary timing requirement
     */
    public static function inappropriateAnniversaryTiming(Title $title, string $anniversaryRequirement): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be unretired outside of appropriate anniversary timing: {$anniversaryRequirement}.");
    }

    /**
     * Title cannot be unretired due to brand legacy restrictions.
     *
     * @param  Title  $title  The title that cannot be unretired
     * @param  string  $legacyRestriction  Description of the brand legacy restriction
     */
    public static function brandLegacyRestriction(Title $title, string $legacyRestriction): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be unretired due to brand legacy restriction: {$legacyRestriction}.");
    }
}
