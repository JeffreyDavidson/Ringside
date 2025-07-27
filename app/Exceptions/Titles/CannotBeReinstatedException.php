<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

/**
 * Exception thrown when a title cannot be reinstated due to business rule violations.
 *
 * This exception handles scenarios where title reinstatement is prevented by current state
 * or business logic constraints in wrestling promotion championship management.
 *
 * BUSINESS CONTEXT:
 * Title reinstatement represents bringing previously pulled or inactive championships back
 * into active competition, restoring their place in ongoing storylines and booking plans.
 * This differs from debut (first-time activation) and unretirement (returning retired titles).
 *
 * COMMON SCENARIOS:
 * - Attempting to reinstate already active or never-pulled titles
 * - Trying to reinstate permanently retired championships
 * - Reinstatement conflicts with existing championship structures or replacement titles
 * - Missing prerequisites for proper comeback workflow and storyline integration
 * - Conflicts with ongoing feuds or championship programs
 * - Brand division restrictions preventing cross-promotional reinstatements
 *
 * BUSINESS IMPACT:
 * - Prevents invalid championship reinstatements and duplicate title competition
 * - Maintains title lifecycle integrity and championship lineage accuracy
 * - Protects comeback storylines and championship return event planning
 * - Ensures proper regulatory compliance and reinstatement authorization protocols
 * - Preserves the significance of championship pulls and their eventual returns
 */
final class CannotBeReinstatedException extends BaseBusinessException
{
    /**
     * Title is already in an active state and cannot be reinstated.
     *
     * @param  Title  $title  The title that cannot be reinstated
     */
    public static function active(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} is already active and cannot be reinstated.");
    }

    /**
     * Title cannot be reinstated because it has been permanently retired.
     *
     * @param  Title  $title  The title that cannot be reinstated
     */
    public static function retired(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} is retired and cannot be reinstated.");
    }

    /**
     * Title has never been activated and cannot be reinstated.
     *
     * @param  Title  $title  The title that cannot be reinstated
     */
    public static function neverActivated(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} has never been activated and cannot be reinstated. Use debut workflow instead.");
    }

    /**
     * Title cannot be reinstated due to replacement title conflicts.
     *
     * @param  Title  $title  The title that cannot be reinstated
     * @param  string  $replacementTitle  Name of the replacement title causing conflict
     */
    public static function replacementExists(Title $title, string $replacementTitle): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be reinstated because replacement championship '{$replacementTitle}' already exists.");
    }

    /**
     * Title cannot be reinstated due to missing required prerequisites.
     *
     * @param  Title  $title  The title that cannot be reinstated
     * @param  string  $prerequisite  Description of the missing prerequisite
     */
    public static function missingPrerequisite(Title $title, string $prerequisite): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be reinstated due to missing prerequisite: {$prerequisite}.");
    }

    /**
     * Title cannot be reinstated due to business rule conflicts.
     *
     * @param  Title  $title  The title that cannot be reinstated
     * @param  string  $conflict  Description of the business rule conflict
     */
    public static function businessRuleConflict(Title $title, string $conflict): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be reinstated due to business rule conflict: {$conflict}.");
    }

    /**
     * Title cannot be reinstated without proper authorization.
     *
     * @param  Title  $title  The title that cannot be reinstated
     * @param  string  $authorizationLevel  Required authorization level for reinstatement
     */
    public static function unauthorizedReinstatement(Title $title, string $authorizationLevel): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be reinstated without {$authorizationLevel} authorization.");
    }

    /**
     * Title cannot be reinstated due to ongoing championship feud conflicts.
     *
     * @param  Title  $title  The title that cannot be reinstated
     * @param  string  $feudDetails  Description of the conflicting championship feud
     */
    public static function ongoingFeudConflict(Title $title, string $feudDetails): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be reinstated due to ongoing championship feud: {$feudDetails}.");
    }

    /**
     * Title cannot be reinstated due to brand division restrictions.
     *
     * @param  Title  $title  The title that cannot be reinstated
     * @param  string  $brandRestriction  Description of the brand division restriction
     */
    public static function brandRestriction(Title $title, string $brandRestriction): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be reinstated due to brand restriction: {$brandRestriction}.");
    }

    /**
     * Title cannot be reinstated due to storyline continuity issues.
     *
     * @param  Title  $title  The title that cannot be reinstated
     * @param  string  $storylineIssue  Description of the storyline continuity issue
     */
    public static function storylineContinuity(Title $title, string $storylineIssue): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be reinstated due to storyline continuity issue: {$storylineIssue}.");
    }

    /**
     * Title cannot be reinstated due to insufficient time since pulling.
     *
     * @param  Title  $title  The title that cannot be reinstated
     * @param  string  $minimumTimeframe  Required minimum timeframe before reinstatement
     */
    public static function insufficientCoolingPeriod(Title $title, string $minimumTimeframe): static
    {
        $context = self::formatModelContext($title);

        return new static("{$context} cannot be reinstated yet - requires minimum {$minimumTimeframe} cooling period.");
    }
}
