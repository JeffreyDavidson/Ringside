<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Exceptions\Concerns\ProvidesRosterExceptionContext;
use App\Models\TagTeams\TagTeam;
use Exception;

/**
 * Exception thrown when a tag team cannot be suspended due to business rule violations.
 *
 * This exception provides detailed context about why a tag team suspension failed,
 * including employment status conflicts, existing suspension states, and administrative
 * requirements that prevent the suspension operation.
 *
 * BUSINESS CONTEXT:
 * Tag team suspension is a disciplinary action that temporarily removes teams from
 * active competition while maintaining employment status. Suspensions require proper
 * authorization, valid employment status, and consideration of championship obligations.
 *
 * USAGE EXAMPLES:
 * - Teams must be employed to be suspended
 * - Teams cannot be suspended if already suspended
 * - Disciplinary authorization may be required
 * - Championship obligations may affect suspension timing
 *
 * @example
 * ```php
 * // Check suspension eligibility
 * try {
 *     $tagTeam->ensureCanBeSuspended();
 *     SuspendAction::run($tagTeam);
 * } catch (CannotBeSuspendedException $e) {
 *     // Handle suspension conflict
 *     logger()->warning('Tag team suspension blocked', [
 *         'reason' => $e->getMessage(),
 *         'tag_team' => $e->getTagTeamContext()
 *     ]);
 * }
 * ```
 */
class CannotBeSuspendedException extends Exception
{
    use ProvidesRosterExceptionContext;

    /**
     * Create exception for attempting to suspend an unemployed tag team.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be suspended
     * @return static Exception instance with unemployment context
     */
    public static function notEmployed(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be suspended because it is not currently employed. Only employed tag teams can be suspended from active competition.");
    }

    /**
     * Create exception for attempting to suspend an already suspended tag team.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be suspended
     * @return static Exception instance with existing suspension context
     */
    public static function alreadySuspended(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be suspended because it is already suspended. Review current suspension status or consider reinstatement before applying new disciplinary measures.");
    }

    /**
     * Create exception for suspension blocked by championship obligations.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be suspended
     * @param  string  $championshipDetails  Details about championship conflicts
     * @return static Exception instance with championship context
     */
    public static function hasChampionshipObligations(TagTeam $tagTeam, string $championshipDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be suspended due to current championship obligations: {$championshipDetails}. Championship holders require special consideration for disciplinary actions.");
    }

    /**
     * Create exception for suspension requiring special authorization.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be suspended
     * @param  string  $authorizationLevel  Required authorization level
     * @return static Exception instance with authorization context
     */
    public static function requiresAuthorization(TagTeam $tagTeam, string $authorizationLevel): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} suspension requires {$authorizationLevel} authorization. Disciplinary actions must follow proper administrative approval channels.");
    }

    /**
     * Create exception for suspension blocked by scheduled match commitments.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be suspended
     * @param  string  $matchDetails  Details about scheduled matches
     * @return static Exception instance with match commitment context
     */
    public static function hasScheduledMatches(TagTeam $tagTeam, string $matchDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be suspended due to scheduled match commitments: {$matchDetails}. Resolve match obligations before applying disciplinary measures.");
    }

    /**
     * Create exception for suspension timing conflicts with storylines.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be suspended
     * @param  string  $storylineDetails  Details about active storylines
     * @return static Exception instance with storyline context
     */
    public static function storylineConflicts(TagTeam $tagTeam, string $storylineDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} suspension conflicts with active storylines: {$storylineDetails}. Coordinate with creative team before disciplinary actions.");
    }

    /**
     * Get tag team context for logging and debugging.
     *
     * @return array<string, mixed> Tag team details for context
     */
    public function getTagTeamContext(): array
    {
        return $this->getRosterMemberContext();
    }
}
