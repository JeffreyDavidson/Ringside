<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Exceptions\BaseBusinessException;
use App\Models\TagTeams\TagTeam;

/**
 * Exception thrown when a tag team cannot be released due to business rule violations.
 *
 * This exception provides detailed context about why a tag team release failed,
 * including employment status requirements, contractual obligations, and championship
 * commitments that prevent the release operation.
 *
 * BUSINESS CONTEXT:
 * Tag team release terminates employment relationships and ends all current partnerships
 * and management arrangements. Releases require proper employment status, fulfillment
 * of contractual obligations, and resolution of championship commitments.
 *
 * USAGE EXAMPLES:
 * - Teams must be employed to be released
 * - Championship obligations may block release
 * - Contractual commitments must be fulfilled
 * - Scheduled match obligations require resolution
 *
 * @example
 * ```php
 * // Check release eligibility
 * try {
 *     $tagTeam->ensureCanBeReleased();
 *     ReleaseAction::run($tagTeam);
 * } catch (CannotBeReleasedException $e) {
 *     // Handle release conflict
 *     logger()->warning('Tag team release blocked', [
 *         'reason' => $e->getMessage(),
 *         'tag_team' => $e->getTagTeamContext()
 *     ]);
 * }
 * ```
 */
class CannotBeReleasedException extends BaseBusinessException
{
    /**
     * Create exception for attempting to release an unemployed tag team.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be released
     * @return static Exception instance with employment status context
     */
    public static function notEmployed(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be released because it is not currently employed. Only employed tag teams can be released from their contracts.");
    }

    /**
     * Create exception for release blocked by championship obligations.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be released
     * @param  string  $championshipDetails  Details about championship conflicts
     * @return static Exception instance with championship context
     */
    public static function hasChampionshipObligations(TagTeam $tagTeam, string $championshipDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be released due to current championship obligations: {$championshipDetails}. Championship holders must vacate titles before release.");
    }

    /**
     * Create exception for release blocked by contractual obligations.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be released
     * @param  string  $contractDetails  Details about unfulfilled contract terms
     * @return static Exception instance with contractual context
     */
    public static function contractualObligations(TagTeam $tagTeam, string $contractDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be released due to unfulfilled contractual obligations: {$contractDetails}. Complete all contract terms before release.");
    }

    /**
     * Create exception for release blocked by scheduled match commitments.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be released
     * @param  string  $matchDetails  Details about scheduled matches
     * @return static Exception instance with match commitment context
     */
    public static function hasScheduledMatches(TagTeam $tagTeam, string $matchDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be released due to scheduled match commitments: {$matchDetails}. Fulfill or reassign match obligations before release.");
    }

    /**
     * Create exception for release requiring special authorization.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be released
     * @param  string  $authorizationLevel  Required authorization level
     * @return static Exception instance with authorization context
     */
    public static function requiresAuthorization(TagTeam $tagTeam, string $authorizationLevel): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} release requires {$authorizationLevel} authorization. High-value contracts require executive approval for termination.");
    }

    /**
     * Create exception for release conflicts with active storylines.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be released
     * @param  string  $storylineDetails  Details about active storylines
     * @return static Exception instance with storyline context
     */
    public static function storylineConflicts(TagTeam $tagTeam, string $storylineDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} release conflicts with active storylines: {$storylineDetails}. Coordinate with creative team to resolve storyline commitments.");
    }

    /**
     * Create exception for release during probationary period.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be released
     * @param  string  $probationDetails  Details about probationary status
     * @return static Exception instance with probationary context
     */
    public static function probationaryPeriod(TagTeam $tagTeam, string $probationDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be released during probationary period: {$probationDetails}. Complete probationary requirements before release consideration.");
    }
}
