<?php

declare(strict_types=1);

namespace App\Exceptions\Matches;

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;

/**
 * Exception thrown when match configuration violates business rules or setup requirements.
 *
 * This exception handles various match setup and configuration errors that occur
 * during event planning and match creation in wrestling promotion management.
 *
 * BUSINESS CONTEXT:
 * Professional wrestling match creation requires precise configuration to ensure fair
 * competition, proper storyline execution, and regulatory compliance. Match configuration
 * encompasses competitor allocation, championship stakes, match type selection, referee
 * assignment, venue compatibility, and rule stipulations. Proper configuration protects
 * match integrity, competitor safety, championship credibility, and promotional standards.
 * Configuration validation prevents booking errors that could damage storylines,
 * competitor relationships, or fan expectations while ensuring operational feasibility.
 *
 * COMMON SCENARIOS:
 * - Invalid competitor distribution for specific match types (singles, tag, multi-person)
 * - Mismatched title requirements and championship eligibility conflicts
 * - Conflicting match rules or stipulations that cannot coexist
 * - Invalid match type selection for event context or venue capabilities
 * - Duplicate competitor assignments within single matches
 * - Missing essential match components like referee assignments
 * - Championship title status conflicts (inactive titles in active matches)
 * - Venue capability mismatches with match requirements
 *
 * BUSINESS IMPACT:
 * - Maintains match quality standards and competitive fairness
 * - Protects championship integrity and title lineage credibility
 * - Ensures proper storyline execution and narrative consistency
 * - Prevents operational failures during live events
 * - Maintains regulatory compliance and safety standards
 * - Protects competitor welfare through proper match structuring
 * - Supports venue operations and technical requirements
 * - Upholds fan expectations and promotional credibility
 */
final class InvalidMatchConfigurationException extends BaseBusinessException
{
    /**
     * Match type has invalid competitor count for its requirements.
     *
     * @param  int  $actualCount  The actual number of competitors provided
     * @param  string  $matchType  The match type with specific count requirements
     */
    public static function invalidCompetitorCount(int $actualCount, string $matchType): self
    {
        return new self(
            "Invalid competitor count for {$matchType}. Got {$actualCount} competitors, but this match type requires a different number."
        );
    }

    /**
     * Match cannot be formed due to insufficient competitor count.
     *
     * @param  int  $required  Minimum number of competitors required
     * @param  int  $actual  Actual number of competitors provided
     */
    public static function insufficientCompetitors(int $required, int $actual): self
    {
        return new self(
            "Insufficient competitors for match. Required: {$required}, Actual: {$actual}. Matches must have at least {$required} competitors."
        );
    }

    /**
     * Match type cannot accommodate the provided number of competitors.
     *
     * @param  int  $maximum  Maximum number of competitors allowed
     * @param  int  $actual  Actual number of competitors provided
     * @param  string  $matchType  The match type with competitor limits
     */
    public static function tooManyCompetitors(int $maximum, int $actual, string $matchType): self
    {
        return new self(
            "Too many competitors for {$matchType}. Maximum: {$maximum}, Actual: {$actual}. This match type cannot accommodate more than {$maximum} competitors."
        );
    }

    /**
     * Multi-competitor match has invalid competitor distribution across sides.
     *
     * @param  array<int, int>  $sides  Array mapping side numbers to competitor counts
     * @param  string  $matchType  The match type requiring specific distribution
     */
    public static function invalidSideDistribution(array $sides, string $matchType): self
    {
        $sideCount = count($sides);
        $distribution = implode(', ', array_map(fn (int $side, int $count) => "Side {$side}: {$count}", array_keys($sides), $sides));

        return new self(
            "Invalid side distribution for {$matchType}. {$sideCount} sides with distribution [{$distribution}]. Check match type requirements for proper competitor allocation."
        );
    }

    /**
     * Title match requires at least one championship title assignment.
     *
     * @param  Model  $match  The match configured as title match without title
     */
    public static function titleMatchWithoutTitle(Model $match): self
    {
        $matchContext = self::formatModelContext($match);

        return new self(
            "{$matchContext} is configured as title match but has no championship title assigned. Title matches require active championship stakes."
        );
    }

    /**
     * Non-title match cannot have championship title assignments.
     *
     * @param  Model  $match  The match configured as non-title with title
     * @param  string  $titleName  Name of the championship inappropriately assigned
     */
    public static function nonTitleMatchWithTitle(Model $match, string $titleName): self
    {
        $matchContext = self::formatModelContext($match);

        return new self(
            "{$matchContext} is configured as non-title match but has championship '{$titleName}' assigned. Remove title or change to title match type."
        );
    }

    /**
     * Match type is not valid for the specified event context.
     *
     * @param  string  $matchType  The invalid match type for this event
     * @param  Model  $event  The event that cannot support this match type
     */
    public static function invalidMatchType(string $matchType, Model $event): self
    {
        $eventContext = self::formatModelContext($event);

        return new self(
            "Match type '{$matchType}' is not valid for {$eventContext}. Check event restrictions and supported match types."
        );
    }

    /**
     * Competitor cannot appear multiple times in the same match.
     *
     * @param  Model  $competitor  The competitor appearing multiple times
     */
    public static function duplicateCompetitor(Model $competitor): self
    {
        $competitorContext = self::formatModelContext($competitor);

        return new self(
            "{$competitorContext} appears multiple times in the same match. Each competitor can only participate once per match."
        );
    }

    /**
     * Match contains conflicting competitor types that cannot compete together.
     *
     * @param  array<int, string>  $types  Array of conflicting competitor types
     */
    public static function conflictingCompetitorTypes(array $types): self
    {
        $typeList = implode(', ', $types);

        return new self(
            "Conflicting competitor types in match: [{$typeList}]. Certain match types require specific competitor combinations."
        );
    }

    /**
     * Match requires at least one qualified referee assignment.
     *
     * @param  Model  $match  The match missing referee assignment
     */
    public static function missingReferee(Model $match): self
    {
        $matchContext = self::formatModelContext($match);

        return new self(
            "{$matchContext} requires at least one qualified referee assignment. All matches must have proper officiating."
        );
    }

    /**
     * Referee cannot be assigned to match due to conflict or unavailability.
     *
     * @param  Model  $referee  The referee with assignment conflict
     * @param  string  $reason  Specific reason for the conflict
     */
    public static function refereeConflict(Model $referee, string $reason): self
    {
        $refereeContext = self::formatModelContext($referee);

        return new self(
            "{$refereeContext} cannot be assigned to match: {$reason}. Assign a different qualified referee."
        );
    }

    /**
     * Match rules or stipulations are invalid or incompatible.
     *
     * @param  string  $rules  The invalid match rules or stipulations
     * @param  string  $reason  Specific reason why rules are invalid
     */
    public static function invalidMatchRules(string $rules, string $reason): self
    {
        return new self(
            "Invalid match rules '{$rules}': {$reason}. Review match stipulations and rule compatibility."
        );
    }

    /**
     * Match has scheduling conflicts that prevent proper booking.
     *
     * @param  Model  $match  The match with scheduling conflicts
     * @param  string  $conflictDetails  Specific details about the scheduling conflict
     */
    public static function schedulingConflict(Model $match, string $conflictDetails): self
    {
        $matchContext = self::formatModelContext($match);

        return new self(
            "Scheduling conflict for {$matchContext}: {$conflictDetails}. Resolve conflicts before finalizing match."
        );
    }

    /**
     * Competitor is not eligible for the specified championship competition.
     *
     * @param  Model  $competitor  The competitor who is not eligible
     * @param  Model  $title  The championship title they're not eligible for
     * @param  string  $reason  Specific reason for ineligibility
     */
    public static function championshipEligibilityViolation(Model $competitor, Model $title, string $reason): static
    {
        $competitorContext = self::formatModelContext($competitor);
        $titleContext = self::formatModelContext($title);

        return new self(
            "{$competitorContext} is not eligible for {$titleContext}: {$reason}. Check title eligibility requirements and competitor status."
        );
    }

    /**
     * Championship title is inactive and cannot be used in matches.
     *
     * @param  Model  $title  The inactive championship title
     */
    public static function inactiveTitle(Model $title): self
    {
        $titleContext = self::formatModelContext($title);

        return new self(
            "{$titleContext} is inactive and cannot be used in matches. Activate the title or remove it from the match."
        );
    }

    /**
     * Venue does not support the capabilities required for this match type.
     *
     * @param  Model  $venue  The venue lacking required capabilities
     * @param  string  $requirement  The specific capability requirement
     */
    public static function venueCapabilityRequired(Model $venue, string $requirement): self
    {
        $venueContext = self::formatModelContext($venue);

        return new self(
            "{$venueContext} does not support required capability: {$requirement}. Choose a venue with appropriate facilities."
        );
    }
}
