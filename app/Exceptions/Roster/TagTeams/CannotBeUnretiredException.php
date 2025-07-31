<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Exceptions\BaseBusinessException;
use App\Models\TagTeams\TagTeam;

/**
 * Exception thrown when a tag team cannot be unretired due to business rule violations.
 *
 * This exception handles scenarios where tag team unretirement is prevented by current state
 * or business logic constraints in wrestling promotion tag team management.
 *
 * BUSINESS CONTEXT:
 * Tag team unretirement allows previously retired partnerships to return to active competition,
 * typically for reunion storylines, nostalgia acts, or when partners reconcile their differences.
 * This represents significant storyline opportunities but must be carefully managed to maintain
 * roster stability and storyline continuity.
 *
 * COMMON SCENARIOS:
 * - Attempting to unretire teams that aren't currently retired
 * - Trying to unretire when partners are unavailable or committed elsewhere
 * - Unretirement conflicts with current storylines or roster commitments
 * - Name conflicts with existing active tag teams
 * - Administrative constraints preventing unauthorized unretirement
 * - Partner compatibility issues preventing effective reunion
 *
 * BUSINESS IMPACT:
 * - Maintains roster stability and prevents partner commitment conflicts
 * - Protects ongoing storylines and current tag team dynamics
 * - Ensures unretired teams have viable partner bases for compelling storylines
 * - Prevents administrative errors and unauthorized partnership resurrections
 * - Supports proper storyline continuity and fan investment management
 */
final class CannotBeUnretiredException extends BaseBusinessException
{
    /**
     * Tag team is not currently retired and cannot be unretired.
     *
     * @param  TagTeam  $tagTeam  The tag team that is not retired
     */
    public static function notRetired(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} is not retired and cannot be unretired.");
    }

    /**
     * Tag team name conflicts with an existing active tag team.
     *
     * @param  TagTeam  $tagTeam  The tag team being unretired
     * @param  string  $conflictingTeamName  Name of the conflicting tag team
     */
    public static function nameConflict(TagTeam $tagTeam, string $conflictingTeamName): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be unretired: name conflicts with existing tag team '{$conflictingTeamName}'.");
    }

    /**
     * No current partners are available for unretirement.
     *
     * @param  TagTeam  $tagTeam  The tag team being unretired
     */
    public static function noAvailablePartners(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be unretired: no current partners are available.");
    }

    /**
     * Insufficient partners available for viable unretirement.
     *
     * @param  TagTeam  $tagTeam  The tag team being unretired
     * @param  int  $availableCount  Number of available partners
     * @param  int  $minimumRequired  Minimum partners required
     */
    public static function insufficientPartners(TagTeam $tagTeam, int $availableCount, int $minimumRequired): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be unretired: only {$availableCount} partners available, but {$minimumRequired} required.");
    }

    /**
     * Key partners are unavailable for unretirement.
     *
     * @param  TagTeam  $tagTeam  The tag team being unretired
     * @param  string  $unavailablePartners  Names of unavailable key partners
     */
    public static function keyPartnersUnavailable(TagTeam $tagTeam, string $unavailablePartners): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be unretired: key partners unavailable: {$unavailablePartners}.");
    }

    /**
     * Partners have conflicting current commitments.
     *
     * @param  TagTeam  $tagTeam  The tag team being unretired
     * @param  string  $conflictingCommitments  Description of partner conflicts
     */
    public static function partnerCommitmentConflicts(TagTeam $tagTeam, string $conflictingCommitments): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be unretired due to partner commitment conflicts: {$conflictingCommitments}.");
    }

    /**
     * Unretirement would conflict with active storylines.
     *
     * @param  TagTeam  $tagTeam  The tag team being unretired
     * @param  string  $storylineConflicts  Description of storyline conflicts
     */
    public static function storylineConflicts(TagTeam $tagTeam, string $storylineConflicts): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be unretired due to storyline conflicts: {$storylineConflicts}.");
    }

    /**
     * Unretirement requires proper administrative authorization.
     *
     * @param  TagTeam  $tagTeam  The tag team being unretired
     * @param  string  $authorizationLevel  Required authorization level
     */
    public static function insufficientAuthorization(TagTeam $tagTeam, string $authorizationLevel): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be unretired without {$authorizationLevel} authorization.");
    }

    /**
     * Tag team was permanently retired and cannot be unretired.
     *
     * @param  TagTeam  $tagTeam  The tag team that was permanently retired
     */
    public static function permanentlyRetired(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} was permanently retired and cannot be unretired.");
    }

    /**
     * Unretirement timing conflicts with scheduled events.
     *
     * @param  TagTeam  $tagTeam  The tag team being unretired
     * @param  string  $eventConflicts  Description of event timing conflicts
     */
    public static function eventTimingConflicts(TagTeam $tagTeam, string $eventConflicts): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be unretired due to event timing conflicts: {$eventConflicts}.");
    }
}
