<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Exceptions\BaseBusinessException;
use App\Models\TagTeams\TagTeam;

/**
 * Exception thrown when a tag team cannot be reinstated due to business rule violations.
 *
 * This exception provides detailed context about why a tag team reinstatement failed,
 * including suspension status conflicts, employment requirements, and administrative
 * authorization that prevent the reinstatement operation.
 *
 * BUSINESS CONTEXT:
 * Tag team reinstatement restores suspended teams to active competition status while
 * maintaining employment continuity. Reinstatements require proper authorization,
 * valid suspension status, and consideration of partner availability.
 *
 * USAGE EXAMPLES:
 * - Teams must be suspended to be reinstated
 * - Teams must still be employed during suspension
 * - Administrative clearance may be required
 * - Partner availability affects reinstatement timing
 *
 * @example
 * ```php
 * // Check reinstatement eligibility
 * try {
 *     $tagTeam->ensureCanBeReinstated();
 *     ReinstateAction::run($tagTeam);
 * } catch (CannotBeReinstatedException $e) {
 *     // Handle reinstatement conflict
 *     logger()->warning('Tag team reinstatement blocked', [
 *         'reason' => $e->getMessage(),
 *         'tag_team' => $e->getTagTeamContext()
 *     ]);
 * }
 * ```
 */
class CannotBeReinstatedException extends BaseBusinessException
{
    /**
     * Create exception for attempting to reinstate a non-suspended tag team.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be reinstated
     * @return static Exception instance with suspension status context
     */
    public static function notSuspended(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be reinstated because it is not currently suspended. Only suspended tag teams can be reinstated to active competition.");
    }

    /**
     * Create exception for attempting to reinstate an unemployed tag team.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be reinstated
     * @return static Exception instance with employment context
     */
    public static function notEmployed(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be reinstated because it is no longer employed. Tag teams must maintain employment status during suspension periods.");
    }

    /**
     * Create exception for reinstatement requiring administrative authorization.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be reinstated
     * @param  string  $authorizationLevel  Required authorization level
     * @return static Exception instance with authorization context
     */
    public static function requiresAuthorization(TagTeam $tagTeam, string $authorizationLevel): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} reinstatement requires {$authorizationLevel} authorization. Disciplinary clearance must follow proper administrative approval channels.");
    }

    /**
     * Create exception for reinstatement blocked by partner unavailability.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be reinstated
     * @param  string  $partnerDetails  Details about unavailable partners
     * @return static Exception instance with partner availability context
     */
    public static function partnersUnavailable(TagTeam $tagTeam, string $partnerDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be reinstated due to partner unavailability: {$partnerDetails}. All partners must be available for team reinstatement.");
    }

    /**
     * Create exception for reinstatement requiring disciplinary clearance.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be reinstated
     * @param  string  $clearanceDetails  Details about required clearance
     * @return static Exception instance with disciplinary clearance context
     */
    public static function requiresDisciplinaryClearance(TagTeam $tagTeam, string $clearanceDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} reinstatement requires disciplinary clearance: {$clearanceDetails}. Complete all required disciplinary measures before reinstatement.");
    }

    /**
     * Create exception for reinstatement timing conflicts.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be reinstated
     * @param  string  $conflictDetails  Details about timing conflicts
     * @return static Exception instance with timing conflict context
     */
    public static function timingConflicts(TagTeam $tagTeam, string $conflictDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} reinstatement conflicts with existing schedules: {$conflictDetails}. Coordinate timing with event planning and storyline development.");
    }
}
