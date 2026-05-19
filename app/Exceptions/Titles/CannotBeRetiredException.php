<?php

declare(strict_types=1);

namespace App\Exceptions\Titles;

use App\Exceptions\BaseBusinessException;
use App\Models\Titles\Title;

/**
 * Exception thrown when a title cannot be retired due to business rule violations.
 *
 * This exception handles scenarios where title retirement is prevented by current state
 * or business logic constraints in wrestling promotion championship management.
 *
 * BUSINESS CONTEXT:
 * Title retirement represents the permanent cessation of championships from active competition,
 * marking their transition from active booking to historical legacy status.
 * This differs from pulling (temporary removal) and deactivation (general status change).
 *
 * COMMON SCENARIOS:
 * - Attempting to retire unactivated or already retired championships
 * - Trying to retire titles with active championship reigns or scheduled defenses
 * - Retirement conflicts with ongoing storylines, feuds, or tournaments
 * - Missing prerequisites for proper retirement ceremony and legacy documentation
 * - Insufficient championship history or significance for retirement eligibility
 * - Contractual obligations preventing permanent championship cessation
 *
 * BUSINESS IMPACT:
 * - Maintains championship lifecycle integrity and lineage accuracy
 * - Protects legacy ceremony planning and Hall of Fame eligibility processes
 * - Ensures proper championship record keeping and historical documentation
 * - Preserves the significance of retirement as a permanent championship milestone
 * - Prevents premature retirements that could damage championship prestige
 */
final class CannotBeRetiredException extends BaseBusinessException
{
    /**
     * Title is unactivated and cannot be retired.
     *
     * @param  Title  $title  The title that cannot be retired
     */
    public static function unactivated(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} has never been activated and cannot be retired.");
    }

    /**
     * Title has future debut and cannot be retired before debut begins.
     *
     * @param  Title  $title  The title that cannot be retired
     */
    public static function hasFutureDebut(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} has future debut scheduled and cannot be retired before activation.");
    }

    /**
     * Title is already retired and cannot be retired again.
     *
     * @param  Title  $title  The title that cannot be retired
     */
    public static function alreadyRetired(Title $title): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} is already retired and cannot be retired again.");
    }

    /**
     * Title cannot be retired while it has an active champion.
     *
     * @param  Title  $title  The title that cannot be retired
     * @param  string  $championName  Name of the current champion
     */
    public static function hasActiveChampion(Title $title, string $championName): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be retired while {$championName} holds the championship.");
    }

    /**
     * Title cannot be retired due to unresolved contractual obligations.
     *
     * @param  Title  $title  The title that cannot be retired
     * @param  string  $contractualObligation  Description of the contractual obligation
     */
    public static function contractualObligation(Title $title, string $contractualObligation): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be retired due to unresolved contractual obligation: {$contractualObligation}.");
    }

    /**
     * Title cannot be retired without proper authorization.
     *
     * @param  Title  $title  The title that cannot be retired
     * @param  string  $authorizationLevel  Required authorization level for retirement
     */
    public static function unauthorizedRetirement(Title $title, string $authorizationLevel): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be retired without {$authorizationLevel} authorization.");
    }

    /**
     * Title cannot be retired due to pending championship defenses.
     *
     * @param  Title  $title  The title that cannot be retired
     * @param  int  $pendingDefenseCount  Number of pending championship defenses
     */
    public static function pendingDefenses(Title $title, int $pendingDefenseCount): static
    {
        $context = self::formatModelContext($title);
        $defenseText = $pendingDefenseCount === 1 ? 'defense' : 'defenses';

        return new self("{$context} cannot be retired with {$pendingDefenseCount} pending championship {$defenseText}.");
    }

    /**
     * Title cannot be retired due to historical significance requirements.
     *
     * @param  Title  $title  The title that cannot be retired
     * @param  string  $significanceDetails  Description of the historical significance requirements
     */
    public static function historicalSignificance(Title $title, string $significanceDetails): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be retired due to historical significance requirements: {$significanceDetails}.");
    }

    /**
     * Title cannot be retired due to active storyline involvement.
     *
     * @param  Title  $title  The title that cannot be retired
     * @param  string  $storylineDetails  Description of the active storyline
     */
    public static function activeStoryline(Title $title, string $storylineDetails): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be retired during active storyline involvement: {$storylineDetails}.");
    }

    /**
     * Title cannot be retired due to tournament involvement.
     *
     * @param  Title  $title  The title that cannot be retired
     * @param  string  $tournamentDetails  Description of the tournament involvement
     */
    public static function tournamentInvolvement(Title $title, string $tournamentDetails): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be retired during tournament involvement: {$tournamentDetails}.");
    }

    /**
     * Title cannot be retired due to insufficient championship history.
     *
     * @param  Title  $title  The title that cannot be retired
     * @param  string  $minimumRequirement  Description of the minimum history requirement
     */
    public static function insufficientHistory(Title $title, string $minimumRequirement): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be retired due to insufficient championship history: {$minimumRequirement}.");
    }

    /**
     * Title cannot be retired due to successor championship requirements.
     *
     * @param  Title  $title  The title that cannot be retired
     * @param  string  $successorRequirement  Description of the successor championship requirement
     */
    public static function requiresSuccessor(Title $title, string $successorRequirement): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be retired without proper successor championship: {$successorRequirement}.");
    }

    /**
     * Title cannot be retired due to legacy ceremony prerequisites.
     *
     * @param  Title  $title  The title that cannot be retired
     * @param  string  $ceremonyPrerequisite  Description of the missing ceremony prerequisite
     */
    public static function legacyCeremonyPrerequisite(Title $title, string $ceremonyPrerequisite): static
    {
        $context = self::formatModelContext($title);

        return new self("{$context} cannot be retired without completing legacy ceremony prerequisite: {$ceremonyPrerequisite}.");
    }
}
