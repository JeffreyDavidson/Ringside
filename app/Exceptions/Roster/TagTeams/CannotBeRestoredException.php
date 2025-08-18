<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Exceptions\BaseBusinessException;
use App\Models\TagTeams\TagTeam;

/**
 * Exception thrown when a tag team cannot be restored due to business rule violations.
 *
 * This exception provides detailed context about why a tag team restoration failed,
 * including deletion status requirements, name conflicts, and authorization needs
 * that prevent the restoration operation.
 *
 * BUSINESS CONTEXT:
 * Tag team restoration recovers soft-deleted teams back to active roster management.
 * Restorations require proper deletion status, resolution of name conflicts, and
 * consideration of data integrity and administrative authorization.
 *
 * USAGE EXAMPLES:
 * - Teams must be soft-deleted to be restored
 * - Team names must not conflict with existing active teams
 * - Administrative authorization may be required
 * - Data integrity must be validated
 *
 * @example
 * ```php
 * // Check restoration eligibility
 * try {
 *     $tagTeam->ensureCanBeRestored();
 *     RestoreAction::run($tagTeam);
 * } catch (CannotBeRestoredException $e) {
 *     // Handle restoration conflict
 *     logger()->warning('Tag team restoration blocked', [
 *         'reason' => $e->getMessage(),
 *         'tag_team' => $e->getTagTeamContext()
 *     ]);
 * }
 * ```
 */
class CannotBeRestoredException extends BaseBusinessException
{
    /**
     * Create exception for attempting to restore a non-deleted tag team.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be restored
     * @return static Exception instance with deletion status context
     */
    public static function notDeleted(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be restored because it is not deleted. Only soft-deleted tag teams can be restored to active status.");
    }

    /**
     * Create exception for restoration blocked by name conflicts.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be restored
     * @param  string  $conflictingName  Name of the conflicting active team
     * @return static Exception instance with name conflict context
     */
    public static function nameConflict(TagTeam $tagTeam, string $conflictingName): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be restored because the name conflicts with existing active tag team '{$conflictingName}'. Resolve name conflicts before restoration.");
    }

    /**
     * Create exception for restoration requiring administrative authorization.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be restored
     * @return static Exception instance with authorization context
     */
    public static function insufficientAuthorization(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} restoration requires administrative authorization. High-profile tag team restorations must be approved by management.");
    }

    /**
     * Create exception for restoration blocked by data integrity issues.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be restored
     * @return static Exception instance with data integrity context
     */
    public static function dataIntegrityIssues(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be restored due to data integrity issues. Verify and repair data consistency before restoration.");
    }

    /**
     * Create exception for restoration requiring administrative approval.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be restored
     * @return static Exception instance with approval context
     */
    public static function requiresAdministrativeApproval(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} restoration requires administrative approval. Submit restoration request for management review and approval.");
    }

    /**
     * Create exception for restoration blocked by partner unavailability.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be restored
     * @param  string  $partnerDetails  Details about unavailable partners
     * @return static Exception instance with partner availability context
     */
    public static function partnersUnavailable(TagTeam $tagTeam, string $partnerDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be restored due to partner unavailability: {$partnerDetails}. Ensure all key partners are available for restoration.");
    }

    /**
     * Create exception for restoration timing conflicts.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be restored
     * @param  string  $timingDetails  Details about timing conflicts
     * @return static Exception instance with timing context
     */
    public static function timingConflicts(TagTeam $tagTeam, string $timingDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} restoration conflicts with current schedules: {$timingDetails}. Coordinate timing with event planning and roster management.");
    }

    /**
     * Create exception for restoration blocked by legal or contractual issues.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be restored
     * @param  string  $legalDetails  Details about legal or contractual conflicts
     * @return static Exception instance with legal context
     */
    public static function legalConflicts(TagTeam $tagTeam, string $legalDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be restored due to legal or contractual conflicts: {$legalDetails}. Resolve all legal issues before restoration.");
    }
}
