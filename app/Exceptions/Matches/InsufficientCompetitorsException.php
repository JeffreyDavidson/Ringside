<?php

declare(strict_types=1);

namespace App\Exceptions\Matches;

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;

/**
 * Exception thrown when insufficient competitors are available for match requirements.
 *
 * This exception handles scenarios where there are not enough eligible competitors
 * to fulfill match requirements, whether due to availability, qualification, or
 * booking constraints in wrestling promotion management.
 *
 * BUSINESS CONTEXT:
 * Wrestling promotion success depends on having adequate competitor depth across all divisions,
 * match types, and storyline requirements. Competitor availability affects match quality,
 * tournament viability, championship credibility, and event planning. This exception ensures
 * that matches are only created when sufficient qualified participants are available,
 * protecting match integrity and competitor safety while maintaining competitive standards.
 * Proper competitor management includes roster planning, injury recovery scheduling,
 * development pipeline management, and strategic competitor allocation across events.
 *
 * COMMON SCENARIOS:
 * - Not enough wrestlers for multi-person matches or battle royals
 * - Insufficient tag teams for tournament brackets or elimination formats
 * - Limited qualified championship contenders for title opportunities
 * - Roster availability constraints due to injuries, suspensions, or scheduling
 * - Division-specific shortages affecting weight class or gender competitions
 * - Experience level mismatches preventing proper match creation
 * - Venue capacity requirements exceeding available competitor pool
 * - Storyline requirements demanding specific participant counts
 *
 * BUSINESS IMPACT:
 * - Maintains match quality standards and competitive integrity
 * - Protects promotion reputation through adequate competitor depth
 * - Ensures tournament formats can be completed as advertised
 * - Prevents overworking limited competitor pools and injury risks
 * - Maintains championship credibility through qualified challenger pools
 * - Supports strategic roster development and recruitment planning
 * - Enables proper event planning and fan expectation management
 */
final class InsufficientCompetitorsException extends BaseBusinessException
{
    /**
     * Match type requires more competitors than are currently available.
     *
     * @param  string  $matchType  The specific match type requiring competitors
     * @param  int  $required  Number of competitors required for this match type
     * @param  int  $available  Number of competitors currently available
     */
    public static function forMatchType(string $matchType, int $required, int $available): self
    {
        return new self(
            "Insufficient competitors for {$matchType}. Required: {$required}, Available: {$available}. This match type needs {$required} eligible competitors to proceed."
        );
    }

    /**
     * Tournament format requires more competitors than are currently available.
     *
     * @param  string  $tournamentName  The specific tournament requiring competitors
     * @param  int  $required  Number of competitors required for tournament bracket
     * @param  int  $available  Number of competitors currently available
     */
    public static function forTournament(string $tournamentName, int $required, int $available): self
    {
        return new self(
            "Insufficient competitors for '{$tournamentName}' tournament. Required: {$required}, Available: {$available}. Tournament brackets require exact competitor counts."
        );
    }

    /**
     * Championship title has insufficient qualified contenders for competition.
     *
     * @param  Model  $title  The championship title requiring contenders
     * @param  int  $required  Number of contenders required for title competition
     * @param  int  $qualified  Number of currently qualified contenders
     */
    public static function qualifiedForTitle(Model $title, int $required, int $qualified): self
    {
        $titleContext = self::formatModelContext($title);

        return new self(
            "Insufficient qualified contenders for {$titleContext}. Required: {$required}, Qualified: {$qualified}. Title matches require eligible challengers."
        );
    }

    /**
     * Specific competitor type has insufficient participants for match requirements.
     *
     * @param  string  $competitorType  The type of competitor (e.g., 'heavyweight', 'female')
     * @param  int  $required  Number of competitors of this type required
     * @param  int  $available  Number of competitors of this type available
     */
    public static function ofType(string $competitorType, int $required, int $available): self
    {
        return new self(
            "Insufficient {$competitorType} competitors. Required: {$required}, Available: {$available}. This match requires {$required} {$competitorType} participants."
        );
    }

    /**
     * Wrestling match requires more individual wrestlers than are available.
     *
     * @param  int  $required  Number of wrestlers required for the match
     * @param  int  $available  Number of wrestlers currently available
     */
    public static function wrestlers(int $required, int $available): self
    {
        return new self(
            "Insufficient wrestlers for match. Required: {$required}, Available: {$available}. Wrestling matches need sufficient individual competitors."
        );
    }

    /**
     * Tag team match requires more tag teams than are currently available.
     *
     * @param  int  $required  Number of tag teams required for the match
     * @param  int  $available  Number of tag teams currently available
     */
    public static function tagTeams(int $required, int $available): self
    {
        return new self(
            "Insufficient tag teams for match. Required: {$required}, Available: {$available}. Tag team matches require multiple active teams."
        );
    }

    /**
     * Active roster has insufficient members to meet match requirements.
     *
     * @param  int  $required  Number of active roster members required
     * @param  int  $active  Number of currently active roster members
     * @param  int  $total  Total number of roster members (including inactive)
     */
    public static function activeRoster(int $required, int $active, int $total): self
    {
        return new self(
            "Insufficient active roster members. Required: {$required}, Active: {$active} (Total: {$total}). More competitors need to be employed/activated."
        );
    }

    /**
     * Specific division has insufficient competitors for match requirements.
     *
     * @param  string  $division  The division name requiring competitors
     * @param  int  $required  Number of competitors required in this division
     * @param  int  $available  Number of competitors available in this division
     */
    public static function inDivision(string $division, int $required, int $available): self
    {
        return new self(
            "Insufficient competitors in {$division} division. Required: {$required}, Available: {$available}. Division needs more eligible participants."
        );
    }

    /**
     * Weight class has insufficient competitors meeting requirements.
     *
     * @param  string  $weightClass  The weight class name requiring competitors
     * @param  int  $required  Number of competitors required in this weight class
     * @param  int  $available  Number of competitors available in this weight class
     */
    public static function inWeightClass(string $weightClass, int $required, int $available): self
    {
        return new self(
            "Insufficient competitors in {$weightClass} weight class. Required: {$required}, Available: {$available}. Weight class restrictions limit participant pool."
        );
    }

    /**
     * Venue capacity requirements exceed available competitor count.
     *
     * @param  Model  $venue  The venue with capacity requirements
     * @param  int  $requiredCompetitors  Number of competitors required for venue
     * @param  int  $availableCompetitors  Number of competitors currently available
     */
    public static function forVenueCapacity(Model $venue, int $requiredCompetitors, int $availableCompetitors): self
    {
        $venueContext = self::formatModelContext($venue);

        return new self(
            "Insufficient competitors for {$venueContext} capacity requirements. Required: {$requiredCompetitors}, Available: {$availableCompetitors}. Venue size demands more participants."
        );
    }

    /**
     * Experience level requirements exceed available qualified competitors.
     *
     * @param  string  $experienceLevel  The required experience level
     * @param  int  $required  Number of competitors required with this experience
     * @param  int  $available  Number of competitors available with this experience
     */
    public static function withExperience(string $experienceLevel, int $required, int $available): self
    {
        return new self(
            "Insufficient {$experienceLevel} competitors. Required: {$required}, Available: {$available}. Match requires participants with {$experienceLevel} experience level."
        );
    }

    /**
     * Storyline requirements exceed available competitor count.
     *
     * @param  string  $storyline  The storyline name requiring specific competitors
     * @param  int  $required  Number of competitors required for storyline
     * @param  int  $available  Number of competitors available for storyline
     */
    public static function forStoryline(string $storyline, int $required, int $available): self
    {
        return new self(
            "Insufficient competitors for '{$storyline}' storyline. Required: {$required}, Available: {$available}. Storyline development requires specific participant count."
        );
    }

    /**
     * Insufficient substitute competitors available for contingency planning.
     *
     * @param  int  $required  Number of substitute competitors required
     * @param  int  $available  Number of substitute competitors available
     */
    public static function substitutes(int $required, int $available): self
    {
        return new self(
            "Insufficient substitute competitors. Required: {$required}, Available: {$available}. Backup competitors needed for contingency planning."
        );
    }

    /**
     * Specific date has insufficient competitor availability.
     *
     * @param  string  $date  The date when competitors are needed
     * @param  int  $required  Number of competitors required on this date
     * @param  int  $available  Number of competitors available on this date
     * @param  string  $reason  Optional reason for the shortage
     */
    public static function onDate(string $date, int $required, int $available, string $reason = ''): self
    {
        $reasonText = $reason ? " ({$reason})" : '';

        return new self(
            "Insufficient competitors available on {$date}. Required: {$required}, Available: {$available}{$reasonText}. Schedule conflicts reduce participant availability."
        );
    }

    /**
     * Gender-specific division has insufficient competitors available.
     *
     * @param  string  $gender  The gender category requiring competitors
     * @param  int  $required  Number of competitors required for this gender
     * @param  int  $available  Number of competitors available for this gender
     */
    public static function ofGender(string $gender, int $required, int $available): self
    {
        return new self(
            "Insufficient {$gender} competitors. Required: {$required}, Available: {$available}. Gender-specific divisions require adequate representation."
        );
    }

    /**
     * Elimination process has reduced competitors below minimum requirements.
     *
     * @param  string  $context  The elimination context (tournament, match, etc.)
     * @param  int  $remaining  Number of competitors remaining after eliminations
     * @param  int  $required  Minimum number of competitors required to continue
     */
    public static function afterEliminations(string $context, int $remaining, int $required): self
    {
        return new self(
            "Insufficient competitors remaining in {$context}. Remaining: {$remaining}, Required: {$required}. Eliminations have reduced participants below requirements."
        );
    }

    /**
     * Specific qualification requirements exceed available qualified competitors.
     *
     * @param  string  $qualification  The specific qualification required
     * @param  int  $required  Number of competitors required with this qualification
     * @param  int  $qualified  Number of competitors currently meeting qualification
     */
    public static function withQualification(string $qualification, int $required, int $qualified): self
    {
        return new self(
            "Insufficient competitors with {$qualification} qualification. Required: {$required}, Qualified: {$qualified}. Special qualifications limit eligible participants."
        );
    }

    /**
     * Roster size constraints prevent additional competitor bookings.
     *
     * @param  int  $maxAllowed  Maximum number of competitors allowed on roster
     * @param  int  $currentBooked  Number of competitors currently booked
     * @param  int  $requestedAdditional  Number of additional competitors requested
     */
    public static function rosterConstraints(int $maxAllowed, int $currentBooked, int $requestedAdditional): self
    {
        $totalRequested = $currentBooked + $requestedAdditional;

        return new self(
            "Roster constraints violated. Maximum allowed: {$maxAllowed}, Currently booked: {$currentBooked}, Requested additional: {$requestedAdditional} (Total: {$totalRequested}). Cannot exceed roster limits."
        );
    }
}
