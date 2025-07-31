<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Exceptions\BaseBusinessException;
use App\Models\TagTeams\TagTeam;

/**
 * Exception thrown when a tag team cannot be retired due to business rule violations.
 *
 * This exception handles scenarios where tag team retirement is prevented by current state
 * or business logic constraints in wrestling promotion tag team management.
 *
 * BUSINESS CONTEXT:
 * Tag team retirement marks the formal end of a wrestling partnership, typically due to
 * member conflicts, career changes, injury, or storyline conclusions. Unlike individual
 * wrestler retirement, tag team retirement affects the partnership while individual
 * members may continue their careers as singles competitors.
 *
 * COMMON SCENARIOS:
 * - Attempting to retire inactive or already retired tag teams
 * - Retiring teams with active championship obligations
 * - Retirement conflicts with ongoing storylines or major events
 * - Partnership contractual obligations preventing retirement
 * - Administrative constraints requiring proper authorization
 *
 * BUSINESS IMPACT:
 * - Maintains roster stability and prevents premature partnership endings
 * - Protects ongoing storylines and championship lineages
 * - Ensures member transitions are properly handled
 * - Supports proper administrative oversight of tag team operations
 * - Preserves fan investment and storyline continuity
 */
final class CannotBeRetiredException extends BaseBusinessException
{
    /**
     * Tag team is not currently employed and cannot be retired.
     *
     * @param  TagTeam  $tagTeam  The unemployed tag team
     */
    public static function notEmployed(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} is not currently employed and cannot be retired.");
    }

    /**
     * Tag team is already retired.
     *
     * @param  TagTeam  $tagTeam  The already retired tag team
     */
    public static function alreadyRetired(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} is already retired.");
    }

    /**
     * Tag team has current championship obligations.
     *
     * @param  TagTeam  $tagTeam  The tag team with championship obligations
     * @param  string  $championshipDetails  Details of current championships
     */
    public static function hasChampionshipObligations(TagTeam $tagTeam, string $championshipDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be retired due to championship obligations: {$championshipDetails}.");
    }

    /**
     * Tag team retirement conflicts with active storylines.
     *
     * @param  TagTeam  $tagTeam  The tag team with storyline conflicts
     * @param  string  $storylineConflicts  Details of conflicting storylines
     */
    public static function storylineConflicts(TagTeam $tagTeam, string $storylineConflicts): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be retired due to active storylines: {$storylineConflicts}.");
    }

    /**
     * Tag team has upcoming scheduled matches.
     *
     * @param  TagTeam  $tagTeam  The tag team with scheduled matches
     * @param  string  $matchDetails  Details of upcoming matches
     */
    public static function hasScheduledMatches(TagTeam $tagTeam, string $matchDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be retired due to scheduled matches: {$matchDetails}.");
    }

    /**
     * Partners have contractual obligations preventing retirement.
     *
     * @param  TagTeam  $tagTeam  The tag team with partner contract conflicts
     * @param  string  $contractConflicts  Details of contract conflicts
     */
    public static function partnerContractConflicts(TagTeam $tagTeam, string $contractConflicts): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be retired due to partner contract conflicts: {$contractConflicts}.");
    }

    /**
     * Retirement requires proper administrative authorization.
     *
     * @param  TagTeam  $tagTeam  The tag team requiring authorization
     * @param  string  $authorizationLevel  Required authorization level
     */
    public static function insufficientAuthorization(TagTeam $tagTeam, string $authorizationLevel): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be retired without {$authorizationLevel} authorization.");
    }

    /**
     * Tag team has unresolved disciplinary issues.
     *
     * @param  TagTeam  $tagTeam  The tag team with disciplinary issues
     * @param  string  $disciplinaryIssues  Details of unresolved issues
     */
    public static function unresolvedDisciplinaryIssues(TagTeam $tagTeam, string $disciplinaryIssues): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be retired due to unresolved disciplinary issues: {$disciplinaryIssues}.");
    }

    /**
     * Retirement timing conflicts with major events.
     *
     * @param  TagTeam  $tagTeam  The tag team with timing conflicts
     * @param  string  $majorEventConflicts  Details of major event conflicts
     */
    public static function majorEventConflicts(TagTeam $tagTeam, string $majorEventConflicts): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be retired due to major event conflicts: {$majorEventConflicts}.");
    }

    /**
     * Tag team partners are involved in active feuds.
     *
     * @param  TagTeam  $tagTeam  The tag team with active feuds
     * @param  string  $feudDetails  Details of active feuds
     */
    public static function activeFeudInvolvement(TagTeam $tagTeam, string $feudDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be retired due to active feud involvement: {$feudDetails}.");
    }
}
