<?php

declare(strict_types=1);

namespace App\Exceptions\Roster\TagTeams;

use App\Exceptions\BaseBusinessException;
use App\Models\TagTeams\TagTeam;

/**
 * Exception thrown when a tag team cannot be employed due to business rule violations.
 *
 * This exception handles scenarios where tag team employment is prevented by current state
 * or business logic constraints in wrestling promotion tag team management.
 *
 * BUSINESS CONTEXT:
 * Tag team employment represents the formal hiring of a wrestling partnership by the promotion,
 * making them available for matches, storylines, and championship opportunities. Employment
 * must ensure both partners are available and the team meets promotion standards.
 *
 * COMMON SCENARIOS:
 * - Attempting to employ already employed tag teams
 * - Trying to employ teams with unavailable or conflicted partners
 * - Employment conflicts with partner individual commitments
 * - Teams not meeting promotion employment standards
 * - Administrative constraints requiring proper authorization
 *
 * BUSINESS IMPACT:
 * - Maintains roster integrity and prevents employment conflicts
 * - Ensures employed teams can fulfill match obligations
 * - Protects against partner availability issues
 * - Supports proper administrative oversight of employment decisions
 * - Maintains promotion standards and roster quality
 */
final class CannotBeEmployedException extends BaseBusinessException
{
    /**
     * Tag team is already employed.
     *
     * @param  TagTeam  $tagTeam  The already employed tag team
     */
    public static function alreadyEmployed(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} is already employed.");
    }

    /**
     * Tag team is currently retired and cannot be employed.
     *
     * @param  TagTeam  $tagTeam  The retired tag team
     */
    public static function retired(TagTeam $tagTeam): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} is retired and cannot be employed. Consider unretirement first.");
    }

    /**
     * Tag team partners are not available for employment.
     *
     * @param  TagTeam  $tagTeam  The tag team with unavailable partners
     * @param  string  $unavailablePartners  Details of unavailable partners
     */
    public static function partnersUnavailable(TagTeam $tagTeam, string $unavailablePartners): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be employed due to unavailable partners: {$unavailablePartners}.");
    }

    /**
     * Partners have conflicting employment commitments.
     *
     * @param  TagTeam  $tagTeam  The tag team with partner conflicts
     * @param  string  $employmentConflicts  Details of employment conflicts
     */
    public static function partnerEmploymentConflicts(TagTeam $tagTeam, string $employmentConflicts): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be employed due to partner employment conflicts: {$employmentConflicts}.");
    }

    /**
     * Tag team does not meet promotion employment standards.
     *
     * @param  TagTeam  $tagTeam  The tag team not meeting standards
     * @param  string  $standardsIssues  Details of standards issues
     */
    public static function doesNotMeetStandards(TagTeam $tagTeam, string $standardsIssues): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be employed due to standards issues: {$standardsIssues}.");
    }

    /**
     * Employment requires proper administrative authorization.
     *
     * @param  TagTeam  $tagTeam  The tag team requiring authorization
     * @param  string  $authorizationLevel  Required authorization level
     */
    public static function insufficientAuthorization(TagTeam $tagTeam, string $authorizationLevel): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be employed without {$authorizationLevel} authorization.");
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

        return new self("{$context} cannot be employed due to unresolved disciplinary issues: {$disciplinaryIssues}.");
    }

    /**
     * Employment would exceed roster limits.
     *
     * @param  TagTeam  $tagTeam  The tag team causing roster limit issues
     * @param  int  $currentCount  Current tag team count
     * @param  int  $maximumAllowed  Maximum allowed tag teams
     */
    public static function rosterLimitExceeded(TagTeam $tagTeam, int $currentCount, int $maximumAllowed): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be employed: roster limit exceeded ({$currentCount}/{$maximumAllowed}).");
    }

    /**
     * Employment timing conflicts with scheduled events.
     *
     * @param  TagTeam  $tagTeam  The tag team with timing conflicts
     * @param  string  $eventConflicts  Description of event timing conflicts
     */
    public static function eventTimingConflicts(TagTeam $tagTeam, string $eventConflicts): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self("{$context} cannot be employed due to event timing conflicts: {$eventConflicts}.");
    }
}
