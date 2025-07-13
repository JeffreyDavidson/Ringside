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
 * Wrestling matches require specific numbers and types of competitors based on
 * match type, championship requirements, and business rules. This exception
 * provides specific feedback when competitor requirements cannot be met.
 *
 * COMMON SCENARIOS:
 * - Not enough wrestlers for multi-person matches
 * - Insufficient tag teams for tournament brackets
 * - Limited qualified championship contenders
 * - Roster availability constraints
 *
 * @example
 * ```php
 * // Not enough for match type
 * throw InsufficientCompetitorsException::forMatchType('Royal Rumble', 30, 15);
 *
 * // Tournament bracket
 * throw InsufficientCompetitorsException::forTournament('King of the Ring', 16, 12);
 *
 * // Title contenders
 * throw InsufficientCompetitorsException::qualifiedForTitle($title, 1, 0);
 * ```
 */
class InsufficientCompetitorsException extends BaseBusinessException
{
    /**
     * Exception for insufficient competitors for specific match type.
     */
    public static function forMatchType(string $matchType, int $required, int $available): static
    {
        return new self(
            "Insufficient competitors for {$matchType}. Required: {$required}, Available: {$available}. This match type needs {$required} eligible competitors to proceed."
        );
    }

    /**
     * Exception for insufficient competitors for tournament format.
     */
    public static function forTournament(string $tournamentName, int $required, int $available): static
    {
        return new self(
            "Insufficient competitors for '{$tournamentName}' tournament. Required: {$required}, Available: {$available}. Tournament brackets require exact competitor counts."
        );
    }

    /**
     * Exception for insufficient qualified title contenders.
     */
    public static function qualifiedForTitle(Model $title, int $required, int $qualified): static
    {
        $titleName = $title->name ?? "ID: {$title->id}";

        return new self(
            "Insufficient qualified contenders for championship '{$titleName}'. Required: {$required}, Qualified: {$qualified}. Title matches require eligible challengers."
        );
    }

    /**
     * Exception for insufficient competitors of specific type.
     */
    public static function ofType(string $competitorType, int $required, int $available): static
    {
        return new self(
            "Insufficient {$competitorType} competitors. Required: {$required}, Available: {$available}. This match requires {$required} {$competitorType} participants."
        );
    }

    /**
     * Exception for insufficient wrestlers for wrestling-specific match.
     */
    public static function wrestlers(int $required, int $available): static
    {
        return new self(
            "Insufficient wrestlers for match. Required: {$required}, Available: {$available}. Wrestling matches need sufficient individual competitors."
        );
    }

    /**
     * Exception for insufficient tag teams for tag team match.
     */
    public static function tagTeams(int $required, int $available): static
    {
        return new self(
            "Insufficient tag teams for match. Required: {$required}, Available: {$available}. Tag team matches require multiple active teams."
        );
    }

    /**
     * Exception for insufficient active roster members.
     */
    public static function activeRoster(int $required, int $active, int $total): static
    {
        return new self(
            "Insufficient active roster members. Required: {$required}, Active: {$active} (Total: {$total}). More competitors need to be employed/activated."
        );
    }

    /**
     * Exception for insufficient competitors in specific division.
     */
    public static function inDivision(string $division, int $required, int $available): static
    {
        return new self(
            "Insufficient competitors in {$division} division. Required: {$required}, Available: {$available}. Division needs more eligible participants."
        );
    }

    /**
     * Exception for insufficient competitors meeting weight class.
     */
    public static function inWeightClass(string $weightClass, int $required, int $available): static
    {
        return new self(
            "Insufficient competitors in {$weightClass} weight class. Required: {$required}, Available: {$available}. Weight class restrictions limit participant pool."
        );
    }

    /**
     * Exception for insufficient competitors for venue capacity.
     */
    public static function forVenueCapacity(Model $venue, int $requiredCompetitors, int $availableCompetitors): static
    {
        $venueName = $venue->name ?? "ID: {$venue->id}";

        return new self(
            "Insufficient competitors for venue '{$venueName}' capacity requirements. Required: {$requiredCompetitors}, Available: {$availableCompetitors}. Venue size demands more participants."
        );
    }

    /**
     * Exception for insufficient competitors meeting experience requirements.
     */
    public static function withExperience(string $experienceLevel, int $required, int $available): static
    {
        return new self(
            "Insufficient {$experienceLevel} competitors. Required: {$required}, Available: {$available}. Match requires participants with {$experienceLevel} experience level."
        );
    }

    /**
     * Exception for insufficient competitors for storyline requirements.
     */
    public static function forStoryline(string $storyline, int $required, int $available): static
    {
        return new self(
            "Insufficient competitors for '{$storyline}' storyline. Required: {$required}, Available: {$available}. Storyline development requires specific participant count."
        );
    }

    /**
     * Exception for insufficient substitutes/alternates.
     */
    public static function substitutes(int $required, int $available): static
    {
        return new self(
            "Insufficient substitute competitors. Required: {$required}, Available: {$available}. Backup competitors needed for contingency planning."
        );
    }

    /**
     * Exception for insufficient competitors on specific date.
     */
    public static function onDate(string $date, int $required, int $available, string $reason = ''): static
    {
        $reasonText = $reason ? " ({$reason})" : '';

        return new self(
            "Insufficient competitors available on {$date}. Required: {$required}, Available: {$available}{$reasonText}. Schedule conflicts reduce participant availability."
        );
    }

    /**
     * Exception for insufficient gender-specific competitors.
     */
    public static function ofGender(string $gender, int $required, int $available): static
    {
        return new self(
            "Insufficient {$gender} competitors. Required: {$required}, Available: {$available}. Gender-specific divisions require adequate representation."
        );
    }

    /**
     * Exception for insufficient competitors after eliminations.
     */
    public static function afterEliminations(string $context, int $remaining, int $required): static
    {
        return new self(
            "Insufficient competitors remaining in {$context}. Remaining: {$remaining}, Required: {$required}. Eliminations have reduced participants below requirements."
        );
    }

    /**
     * Exception for insufficient competitors with specific qualification.
     */
    public static function withQualification(string $qualification, int $required, int $qualified): static
    {
        return new self(
            "Insufficient competitors with {$qualification} qualification. Required: {$required}, Qualified: {$qualified}. Special qualifications limit eligible participants."
        );
    }

    /**
     * Exception for roster size constraints.
     */
    public static function rosterConstraints(int $maxAllowed, int $currentBooked, int $requestedAdditional): static
    {
        $totalRequested = $currentBooked + $requestedAdditional;

        return new self(
            "Roster constraints violated. Maximum allowed: {$maxAllowed}, Currently booked: {$currentBooked}, Requested additional: {$requestedAdditional} (Total: {$totalRequested}). Cannot exceed roster limits."
        );
    }
}
