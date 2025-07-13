<?php

declare(strict_types=1);

namespace App\Exceptions\Matches;

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;

/**
 * Exception thrown when match configuration violates business rules.
 *
 * This exception handles various match setup and configuration errors that occur
 * during event planning and match creation in wrestling promotion management.
 *
 * BUSINESS CONTEXT:
 * Wrestling matches have complex configuration requirements including competitor
 * counts, match types, championship requirements, and booking rules. This exception
 * provides specific feedback for match configuration violations.
 *
 * COMMON SCENARIOS:
 * - Invalid competitor distribution for match type
 * - Mismatched title requirements
 * - Conflicting match rules
 * - Invalid match type for event context
 *
 * @example
 * ```php
 * // Invalid competitor count
 * throw InvalidMatchConfigurationException::invalidCompetitorCount(2, 'Tag Team Match');
 *
 * // Title match without title
 * throw InvalidMatchConfigurationException::titleMatchWithoutTitle($match);
 *
 * // Invalid match type
 * throw InvalidMatchConfigurationException::invalidMatchType('Ladder Match', $event);
 * ```
 */
class InvalidMatchConfigurationException extends BaseBusinessException
{
    /**
     * Exception for invalid competitor count for match type.
     */
    public static function invalidCompetitorCount(int $actualCount, string $matchType): static
    {
        return new self(
            "Invalid competitor count for {$matchType}. Got {$actualCount} competitors, but this match type requires a different number."
        );
    }

    /**
     * Exception for insufficient competitors to form a match.
     */
    public static function insufficientCompetitors(int $required, int $actual): static
    {
        return new self(
            "Insufficient competitors for match. Required: {$required}, Actual: {$actual}. Matches must have at least {$required} competitors."
        );
    }

    /**
     * Exception for too many competitors for match type.
     */
    public static function tooManyCompetitors(int $maximum, int $actual, string $matchType): static
    {
        return new self(
            "Too many competitors for {$matchType}. Maximum: {$maximum}, Actual: {$actual}. This match type cannot accommodate more than {$maximum} competitors."
        );
    }

    /**
     * Exception for invalid side distribution in multi-competitor match.
     */
    public static function invalidSideDistribution(array $sides, string $matchType): static
    {
        $sideCount = count($sides);
        $distribution = implode(', ', array_map(fn ($side, $count) => "Side {$side}: {$count}", array_keys($sides), $sides));

        return new self(
            "Invalid side distribution for {$matchType}. {$sideCount} sides with distribution [{$distribution}]. Check match type requirements for proper competitor allocation."
        );
    }

    /**
     * Exception for title match without championship title assigned.
     */
    public static function titleMatchWithoutTitle(Model $match): static
    {
        $matchId = $match->id ?? 'new';

        return new self(
            "Title match (ID: {$matchId}) must have at least one championship title assigned. Title matches require active championship stakes."
        );
    }

    /**
     * Exception for non-title match with title assigned.
     */
    public static function nonTitleMatchWithTitle(Model $match, string $titleName): static
    {
        $matchId = $match->id ?? 'new';

        return new self(
            "Non-title match (ID: {$matchId}) cannot have championship '{$titleName}' assigned. Remove title or change to title match type."
        );
    }

    /**
     * Exception for invalid match type for event context.
     */
    public static function invalidMatchType(string $matchType, Model $event): static
    {
        $eventName = $event->name ?? "Event ID: {$event->id}";

        return new self(
            "Match type '{$matchType}' is not valid for event '{$eventName}'. Check event restrictions and supported match types."
        );
    }

    /**
     * Exception for competitor appearing multiple times in same match.
     */
    public static function duplicateCompetitor(Model $competitor): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);

        return new self(
            "{$competitorType} '{$competitorName}' appears multiple times in the same match. Each competitor can only participate once per match."
        );
    }

    /**
     * Exception for conflicting competitor types in match.
     */
    public static function conflictingCompetitorTypes(array $types): static
    {
        $typeList = implode(', ', $types);

        return new self(
            "Conflicting competitor types in match: [{$typeList}]. Certain match types require specific competitor combinations."
        );
    }

    /**
     * Exception for missing required referee assignment.
     */
    public static function missingReferee(Model $match): static
    {
        $matchId = $match->id ?? 'new';

        return new self(
            "Match (ID: {$matchId}) requires at least one qualified referee assignment. All matches must have proper officiating."
        );
    }

    /**
     * Exception for referee conflict or unavailability.
     */
    public static function refereeConflict(Model $referee, string $reason): static
    {
        $refereeName = $referee->name ?? "ID: {$referee->id}";

        return new self(
            "Referee '{$refereeName}' cannot be assigned to match: {$reason}. Assign a different qualified referee."
        );
    }

    /**
     * Exception for invalid match rules or stipulations.
     */
    public static function invalidMatchRules(string $rules, string $reason): static
    {
        return new self(
            "Invalid match rules '{$rules}': {$reason}. Review match stipulations and rule compatibility."
        );
    }

    /**
     * Exception for match scheduling conflicts.
     */
    public static function schedulingConflict(Model $match, string $conflictDetails): static
    {
        $matchId = $match->id ?? 'new';

        return new self(
            "Scheduling conflict for match (ID: {$matchId}): {$conflictDetails}. Resolve conflicts before finalizing match."
        );
    }

    /**
     * Exception for championship eligibility violations.
     */
    public static function championshipEligibilityViolation(Model $competitor, Model $title): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $titleName = $title->name ?? "ID: {$title->id}";
        $competitorType = class_basename($competitor);

        return new self(
            "{$competitorType} '{$competitorName}' is not eligible for championship '{$titleName}'. Check title eligibility requirements and competitor status."
        );
    }

    /**
     * Exception for inactive title being used in match.
     */
    public static function inactiveTitle(Model $title): static
    {
        $titleName = $title->name ?? "ID: {$title->id}";

        return new self(
            "Championship '{$titleName}' is inactive and cannot be used in matches. Activate the title or remove it from the match."
        );
    }

    /**
     * Exception for match requiring specific venue capabilities.
     */
    public static function venueCapabilityRequired(Model $venue, string $requirement): static
    {
        $venueName = $venue->name ?? "ID: {$venue->id}";

        return new self(
            "Venue '{$venueName}' does not support required capability: {$requirement}. Choose a venue with appropriate facilities."
        );
    }
}
