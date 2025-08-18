<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Exceptions\BaseBusinessException;
use App\Models\TagTeams\TagTeam;

/**
 * Exception thrown when a tag team cannot be deleted due to business rule violations.
 *
 * This exception provides detailed context about why a tag team soft deletion failed,
 * including active status conflicts, data integrity requirements, and historical
 * preservation needs that prevent the deletion operation.
 *
 * BUSINESS CONTEXT:
 * Tag team deletion (soft deletion) removes teams from active roster management while
 * preserving historical records. Deletions require teams to be inactive (not employed
 * or suspended), proper data integrity validation, and consideration of historical significance.
 *
 * NOTE ON DELETION TYPE:
 * This handles soft deletion only - tag teams are marked as deleted but data is preserved
 * for historical reporting, championship lineage, and administrative purposes.
 *
 * USAGE EXAMPLES:
 * - Teams must be inactive (not employed or suspended) to be deleted
 * - Historical significance may prevent deletion
 * - Championship history requires preservation
 * - Administrative authorization may be required
 *
 * @example
 * ```php
 * // Check deletion eligibility
 * try {
 *     $tagTeam->ensureCanBeDeleted();
 *     DeleteAction::run($tagTeam);
 * } catch (CannotBeDeletedException $e) {
 *     // Handle deletion conflict
 *     logger()->warning('Tag team deletion blocked', [
 *         'reason' => $e->getMessage(),
 *         'tag_team' => $e->getTagTeamContext()
 *     ]);
 * }
 * ```
 */
class CannotBeDeletedException extends BaseBusinessException
{
    /**
     * Create exception for attempting to delete an employed tag team.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be deleted
     * @return static Exception instance with employment status context
     */
    public static function stillEmployed(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be deleted because it is still employed. Release the tag team from employment before deletion.");
    }

    /**
     * Create exception for attempting to delete a suspended tag team.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be deleted
     * @return static Exception instance with suspension status context
     */
    public static function stillSuspended(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be deleted because it is currently suspended. Resolve suspension status before deletion.");
    }

    /**
     * Create exception for deletion blocked by historical significance.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be deleted
     * @return static Exception instance with historical significance context
     */
    public static function historicalSignificance(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be deleted due to historical significance. Legendary tag teams must be preserved for historical records and fan reference.");
    }

    /**
     * Create exception for deletion blocked by championship history.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be deleted
     * @return static Exception instance with championship history context
     */
    public static function championshipHistory(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be deleted due to championship history. Former champions must be preserved for title lineage and historical reporting.");
    }

    /**
     * Create exception for deletion requiring administrative authorization.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be deleted
     * @return static Exception instance with authorization context
     */
    public static function insufficientAuthorization(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} deletion requires administrative authorization. High-profile tag team deletions must be approved by management.");
    }

    /**
     * Create exception for deletion blocked by data integrity requirements.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be deleted
     * @param  string  $integrityDetails  Details about data integrity conflicts
     * @return static Exception instance with data integrity context
     */
    public static function dataIntegrityIssues(TagTeam $tagTeam, string $integrityDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be deleted due to data integrity requirements: {$integrityDetails}. Resolve data conflicts before deletion.");
    }

    /**
     * Create exception for deletion blocked by active relationships.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be deleted
     * @param  string  $relationshipDetails  Details about active relationships
     * @return static Exception instance with relationship context
     */
    public static function activeRelationships(TagTeam $tagTeam, string $relationshipDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be deleted due to active relationships: {$relationshipDetails}. End all current partnerships and management relationships first.");
    }

    /**
     * Create exception for deletion conflicts with ongoing investigations.
     *
     * @param  TagTeam  $tagTeam  The tag team that cannot be deleted
     * @param  string  $investigationDetails  Details about ongoing investigations
     * @return static Exception instance with investigation context
     */
    public static function ongoingInvestigations(TagTeam $tagTeam, string $investigationDetails): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be deleted due to ongoing investigations: {$investigationDetails}. Complete all investigations before deletion.");
    }
}
