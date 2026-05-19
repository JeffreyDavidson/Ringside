<?php

declare(strict_types=1);

namespace App\Exceptions\Matches;

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Exception thrown when competitor booking conflicts prevent match creation due to business rule violations.
 *
 * This exception handles scenarios where competitor availability or status conflicts
 * prevent proper match booking in wrestling promotion management and event scheduling.
 *
 * BUSINESS CONTEXT:
 * Wrestling competitors (wrestlers, tag teams, stables) have complex availability requirements
 * including active employment status, health conditions, contractual obligations, suspension
 * status, and scheduling constraints. Proper competitor booking ensures match integrity,
 * storyline continuity, and regulatory compliance while protecting competitor welfare.
 * Competition booking requires careful coordination of competitor schedules, health status,
 * contractual obligations, and business storylines to maintain professional standards.
 *
 * COMMON SCENARIOS:
 * - Attempting to double-book competitors for overlapping time slots
 * - Booking injured competitors who require medical clearance
 * - Scheduling suspended competitors during active disciplinary periods
 * - Assigning retired competitors who are no longer active
 * - Violating contractual restrictions or booking window requirements
 * - Medical clearance conflicts and health-related booking restrictions
 * - Age or experience requirements not being met by selected competitors
 * - Travel restrictions preventing competition at specific venues
 *
 * BUSINESS IMPACT:
 * - Maintains competitor welfare and medical safety standards
 * - Protects contractual obligations and legal compliance
 * - Ensures storyline consistency and booking credibility
 * - Prevents scheduling conflicts that could disrupt events
 * - Upholds disciplinary actions and suspension enforcement
 * - Protects promotion reputation through proper competitor management
 * - Maintains fair competition standards and regulatory compliance
 */
final class CompetitorConflictException extends BaseBusinessException
{
    /**
     * Competitor is already booked for same time slot and cannot be double-booked.
     *
     * @param  Model  $competitor  The competitor that is already booked
     * @param  Model  $existingMatch  The existing match booking
     * @param  Model  $newMatch  The new match attempting to book the competitor
     */
    public static function doubleBooked(Model $competitor, Model $existingMatch, Model $newMatch): static
    {
        $context = self::formatModelContext($competitor);
        $existingMatchContext = self::formatModelContext($existingMatch);
        $newMatchContext = self::formatModelContext($newMatch);

        return new self(
            "{$context} is already booked for {$existingMatchContext} and cannot be double-booked for {$newMatchContext}. Resolve scheduling conflict or choose different competitor."
        );
    }

    /**
     * Competitor is currently injured and cannot be booked for matches.
     *
     * @param  Model  $competitor  The competitor that is injured
     * @param  Carbon|null  $injuryDate  Optional date when the injury occurred
     */
    public static function competitorInjured(Model $competitor, ?Carbon $injuryDate = null): static
    {
        $context = self::formatModelContext($competitor);
        $injuryInfo = $injuryDate ? ' since '.self::formatDateContext($injuryDate) : '';

        return new self(
            "{$context} is currently injured{$injuryInfo} and cannot be booked for matches. Wait for recovery or choose available competitor."
        );
    }

    /**
     * Competitor is currently suspended and cannot be booked for matches.
     *
     * @param  Model  $competitor  The competitor that is suspended
     * @param  string|null  $reason  Optional reason for the suspension
     */
    public static function competitorSuspended(Model $competitor, ?string $reason = null): static
    {
        $context = self::formatModelContext($competitor);
        $reasonInfo = $reason ? " (Reason: {$reason})" : '';

        return new self(
            "{$context} is currently suspended{$reasonInfo} and cannot be booked for matches. Resolve suspension or choose available competitor."
        );
    }

    /**
     * Competitor is retired and cannot be booked for matches.
     *
     * @param  Model  $competitor  The competitor that is retired
     * @param  Carbon|null  $retirementDate  Optional date when retirement occurred
     */
    public static function competitorRetired(Model $competitor, ?Carbon $retirementDate = null): static
    {
        $context = self::formatModelContext($competitor);
        $retirementInfo = $retirementDate ? ' since '.self::formatDateContext($retirementDate) : '';

        return new self(
            "{$context} is retired{$retirementInfo} and cannot be booked for matches. Use active competitors only."
        );
    }

    /**
     * Competitor is not currently employed and cannot be booked for matches.
     *
     * @param  Model  $competitor  The competitor that is unemployed
     */
    public static function competitorUnemployed(Model $competitor): static
    {
        $context = self::formatModelContext($competitor);

        return new self(
            "{$context} is not currently employed and cannot be booked for matches. Employ competitor first or choose employed competitor."
        );
    }

    /**
     * Competitor has contractual restrictions preventing match booking.
     *
     * @param  Model  $competitor  The competitor with contractual restrictions
     * @param  string  $restriction  Description of the contractual restriction
     */
    public static function contractualRestriction(Model $competitor, string $restriction): static
    {
        $context = self::formatModelContext($competitor);

        return new self(
            "{$context} has contractual restrictions: {$restriction}. Review contract terms or negotiate modification."
        );
    }

    /**
     * Competitor does not meet specific match requirements.
     *
     * @param  Model  $competitor  The competitor that doesn't meet requirements
     * @param  string  $requirement  Description of the unmet requirement
     */
    public static function doesNotMeetRequirements(Model $competitor, string $requirement): static
    {
        $context = self::formatModelContext($competitor);

        return new self(
            "{$context} does not meet match requirement: {$requirement}. Choose eligible competitor or modify match requirements."
        );
    }

    /**
     * Tag team has insufficient active members for competition.
     *
     * @param  Model  $tagTeam  The tag team with insufficient members
     * @param  int  $activeMembers  Number of currently active members
     * @param  int  $required  Number of members required for competition
     */
    public static function insufficientTagTeamMembers(Model $tagTeam, int $activeMembers, int $required): static
    {
        $context = self::formatModelContext($tagTeam);

        return new self(
            "{$context} has only {$activeMembers} active members but requires {$required} for competition. Ensure sufficient active members or choose different team."
        );
    }

    /**
     * Stable has insufficient active members for competition type.
     *
     * @param  Model  $stable  The stable with insufficient members
     * @param  int  $activeMembers  Number of currently active members
     * @param  int  $required  Number of members required for this competition type
     */
    public static function insufficientStableMembers(Model $stable, int $activeMembers, int $required): static
    {
        $context = self::formatModelContext($stable);

        return new self(
            "{$context} has only {$activeMembers} active members but requires {$required} for this type of competition. Ensure sufficient active members or modify match requirements."
        );
    }

    /**
     * Competitor does not meet minimum age requirements for competition.
     *
     * @param  Model  $competitor  The competitor who doesn't meet age requirements
     * @param  int  $age  Current age of the competitor
     * @param  int  $minimumAge  Minimum age required for this competition
     */
    public static function ageRestriction(Model $competitor, int $age, int $minimumAge): static
    {
        $context = self::formatModelContext($competitor);

        return new self(
            "{$context} is {$age} years old but must be at least {$minimumAge} for this competition. Check age eligibility requirements."
        );
    }

    /**
     * Competitor does not meet experience level requirements for competition.
     *
     * @param  Model  $competitor  The competitor with insufficient experience
     * @param  string  $currentLevel  Current experience level of the competitor
     * @param  string  $requiredLevel  Required experience level for this competition
     */
    public static function experienceRequirement(Model $competitor, string $currentLevel, string $requiredLevel): static
    {
        $context = self::formatModelContext($competitor);

        return new self(
            "{$context} has {$currentLevel} experience level but {$requiredLevel} is required for this competition. Choose experienced competitor or modify requirements."
        );
    }

    /**
     * Competitor is not eligible for championship competition.
     *
     * @param  Model  $competitor  The competitor who is not eligible
     * @param  Model  $title  The championship title they're not eligible for
     * @param  string  $reason  Reason for ineligibility
     */
    public static function notEligibleForTitle(Model $competitor, Model $title, string $reason): static
    {
        $competitorContext = self::formatModelContext($competitor);
        $titleContext = self::formatModelContext($title);

        return new self(
            "{$competitorContext} is not eligible for {$titleContext}: {$reason}. Review title eligibility requirements."
        );
    }

    /**
     * Competitor is current champion and cannot compete for the same title.
     *
     * @param  Model  $competitor  The competitor who is current champion
     * @param  Model  $title  The championship title they currently hold
     */
    public static function currentChampion(Model $competitor, Model $title): static
    {
        $competitorContext = self::formatModelContext($competitor);
        $titleContext = self::formatModelContext($title);

        return new self(
            "{$competitorContext} is the current {$titleContext} champion and cannot compete for the same title. Title matches require challenger competitors."
        );
    }

    /**
     * Competitor has travel restrictions preventing competition at venue.
     *
     * @param  Model  $competitor  The competitor with travel restrictions
     * @param  Model  $venue  The venue where competition is restricted
     * @param  string  $restriction  Description of the travel restriction
     */
    public static function travelRestriction(Model $competitor, Model $venue, string $restriction): static
    {
        $competitorContext = self::formatModelContext($competitor);
        $venueContext = self::formatModelContext($venue);

        return new self(
            "{$competitorContext} cannot compete at {$venueContext}: {$restriction}. Choose different venue or resolve travel restrictions."
        );
    }

    /**
     * Competitor requires medical clearance before competing.
     *
     * @param  Model  $competitor  The competitor requiring medical clearance
     * @param  string  $requirement  Description of the medical clearance requirement
     */
    public static function medicalClearanceRequired(Model $competitor, string $requirement): static
    {
        $context = self::formatModelContext($competitor);

        return new self(
            "{$context} requires medical clearance: {$requirement}. Obtain clearance before booking or choose cleared competitor."
        );
    }

    /**
     * Competitor booking violates minimum notice requirements.
     *
     * @param  Model  $competitor  The competitor with booking window requirements
     * @param  Carbon  $eventDate  The date of the event
     * @param  int  $requiredDaysNotice  Minimum days notice required by competitor
     */
    public static function outsideBookingWindow(Model $competitor, Carbon $eventDate, int $requiredDaysNotice): static
    {
        $context = self::formatModelContext($competitor);
        $daysUntilEvent = now()->diffInDays($eventDate);
        $eventDateFormatted = self::formatDateContext($eventDate);

        return new self(
            "{$context} requires {$requiredDaysNotice} days notice but event on {$eventDateFormatted} is in {$daysUntilEvent} days. Book earlier or choose competitor with shorter notice requirements."
        );
    }
}
