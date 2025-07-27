<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

/**
 * Exception thrown when a title cannot be debuted due to business rule violations.
 *
 * This exception handles scenarios where inaugural title debut is prevented by current state
 * or business logic constraints in wrestling promotion championship management.
 *
 * BUSINESS CONTEXT:
 * Title debut represents the first-time introduction of championships into active competition,
 * establishing inaugural lineages and creating foundational championship history.
 * This differs from reactivation (returning inactive titles) and reinstatement (restoring pulled titles).
 *
 * COMMON SCENARIOS:
 * - Attempting to debut already debuted or previously active titles
 * - Trying to debut titles that are permanently retired
 * - Debut conflicts with existing championship structures or brand divisions
 * - Missing prerequisites for proper inaugural ceremony workflow
 * - Insufficient promotional buildup or storyline development
 * - Conflicting with scheduled championship tournaments or events
 *
 * BUSINESS IMPACT:
 * - Protects the integrity of inaugural championship marketing and storylines
 * - Maintains accurate championship lineage tracking from inception
 * - Ensures proper ceremonial debuts and championship introduction narratives
 * - Prevents confusion between debuts, reactivations, and reinstatements in promotional materials
 * - Preserves the special significance of "first-time" championship introductions
 */
final class CannotBeDebutedException extends BaseBusinessException
{
    /**
     * Title has already been debuted and cannot be debuted again.
     *
     * @param  Title  $title  The title that cannot be debuted
     */
    public static function alreadyDebuted(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} has already been debuted and cannot be debuted again.");
    }

    /**
     * Title is permanently retired and cannot be debuted.
     *
     * @param  Title  $title  The title that cannot be debuted
     */
    public static function retired(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} is retired and cannot be debuted.");
    }

    /**
     * Title cannot be debuted due to missing required prerequisites.
     *
     * @param  Title  $title  The title that cannot be debuted
     * @param  string  $prerequisite  Description of the missing prerequisite
     */
    public static function missingPrerequisite(Title $title, string $prerequisite): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be debuted due to missing prerequisite: {$prerequisite}.");
    }

    /**
     * Title cannot be debuted due to championship structure conflicts.
     *
     * @param  Title  $title  The title that cannot be debuted
     * @param  string  $conflict  Description of the championship structure conflict
     */
    public static function championshipConflict(Title $title, string $conflict): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be debuted due to championship structure conflict: {$conflict}.");
    }

    /**
     * Title cannot be debuted due to promotional scheduling conflicts.
     *
     * @param  Title  $title  The title that cannot be debuted
     * @param  string  $schedulingConflict  Description of the scheduling conflict
     */
    public static function schedulingConflict(Title $title, string $schedulingConflict): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be debuted due to scheduling conflict: {$schedulingConflict}.");
    }

    /**
     * Title cannot be debuted without proper authorization or approval.
     *
     * @param  Title  $title  The title that cannot be debuted
     * @param  string  $authorizationLevel  Required authorization level for title debut
     */
    public static function unauthorizedDebut(Title $title, string $authorizationLevel): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be debuted without {$authorizationLevel} authorization.");
    }

    /**
     * Title cannot be debuted due to active tournament involvement.
     *
     * @param  Title  $title  The title that cannot be debuted
     * @param  string  $tournamentDetails  Description of the tournament conflict
     */
    public static function tournamentConflict(Title $title, string $tournamentDetails): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be debuted during active tournament involvement: {$tournamentDetails}.");
    }

    /**
     * Title cannot be debuted due to insufficient promotional buildup.
     *
     * @param  Title  $title  The title that cannot be debuted
     * @param  string  $buildupRequirement  Description of the required promotional buildup
     */
    public static function insufficientBuildup(Title $title, string $buildupRequirement): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be debuted without proper promotional buildup: {$buildupRequirement}.");
    }

    /**
     * Title cannot be debuted due to brand division restrictions.
     *
     * @param  Title  $title  The title that cannot be debuted
     * @param  string  $brandRestriction  Description of the brand division restriction
     */
    public static function brandRestriction(Title $title, string $brandRestriction): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be debuted due to brand restriction: {$brandRestriction}.");
    }

    /**
     * Title cannot be debuted due to existing similar championship.
     *
     * @param  Title  $title  The title that cannot be debuted
     * @param  string  $existingTitle  Name of the existing similar championship
     */
    public static function similarChampionshipExists(Title $title, string $existingTitle): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be debuted because similar championship '{$existingTitle}' already exists.");
    }
}
