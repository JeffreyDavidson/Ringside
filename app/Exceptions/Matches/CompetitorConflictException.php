<?php

declare(strict_types=1);

namespace App\Exceptions\Matches;

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Exception thrown when competitor booking conflicts occur.
 *
 * This exception handles various competitor availability and booking conflict
 * scenarios that occur during match planning and event scheduling in wrestling
 * promotion management.
 *
 * BUSINESS CONTEXT:
 * Wrestling competitors (wrestlers, tag teams) have complex availability requirements
 * including active status, health conditions, contractual obligations, and scheduling
 * constraints. This exception provides specific feedback for booking violations.
 *
 * COMMON SCENARIOS:
 * - Double-booking competitors for same time slot
 * - Booking injured or suspended competitors
 * - Booking retired competitors
 * - Contract or availability conflicts
 *
 * @example
 * ```php
 * // Double booking
 * throw CompetitorConflictException::doubleBooked($wrestler, $existingMatch, $newMatch);
 *
 * // Injured competitor
 * throw CompetitorConflictException::competitorInjured($wrestler, $injuryDate);
 *
 * // Suspended competitor
 * throw CompetitorConflictException::competitorSuspended($tagTeam, $suspensionReason);
 * ```
 */
class CompetitorConflictException extends BaseBusinessException
{
    /**
     * Exception for competitor being double-booked for same time slot.
     */
    public static function doubleBooked(Model $competitor, Model $existingMatch, Model $newMatch): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);
        $existingMatchId = $existingMatch->id ?? 'unknown';
        $newMatchId = $newMatch->id ?? 'new';

        return new self(
            "{$competitorType} '{$competitorName}' is already booked for match {$existingMatchId} and cannot be double-booked for match {$newMatchId}. Resolve scheduling conflict or choose different competitor."
        );
    }

    /**
     * Exception for attempting to book injured competitor.
     */
    public static function competitorInjured(Model $competitor, ?Carbon $injuryDate = null): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);
        $injuryInfo = $injuryDate ? " since {$injuryDate->format('Y-m-d')}" : '';

        return new self(
            "{$competitorType} '{$competitorName}' is currently injured{$injuryInfo} and cannot be booked for matches. Wait for recovery or choose available competitor."
        );
    }

    /**
     * Exception for attempting to book suspended competitor.
     */
    public static function competitorSuspended(Model $competitor, ?string $reason = null): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);
        $reasonInfo = $reason ? " (Reason: {$reason})" : '';

        return new self(
            "{$competitorType} '{$competitorName}' is currently suspended{$reasonInfo} and cannot be booked for matches. Resolve suspension or choose available competitor."
        );
    }

    /**
     * Exception for attempting to book retired competitor.
     */
    public static function competitorRetired(Model $competitor, ?Carbon $retirementDate = null): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);
        $retirementInfo = $retirementDate ? " since {$retirementDate->format('Y-m-d')}" : '';

        return new self(
            "{$competitorType} '{$competitorName}' is retired{$retirementInfo} and cannot be booked for matches. Use active competitors only."
        );
    }

    /**
     * Exception for attempting to book unemployed competitor.
     */
    public static function competitorUnemployed(Model $competitor): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);

        return new self(
            "{$competitorType} '{$competitorName}' is not currently employed and cannot be booked for matches. Employ competitor first or choose employed competitor."
        );
    }

    /**
     * Exception for competitor having contractual restrictions.
     */
    public static function contractualRestriction(Model $competitor, string $restriction): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);

        return new self(
            "{$competitorType} '{$competitorName}' has contractual restrictions: {$restriction}. Review contract terms or negotiate modification."
        );
    }

    /**
     * Exception for competitor not meeting match requirements.
     */
    public static function doesNotMeetRequirements(Model $competitor, string $requirement): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);

        return new self(
            "{$competitorType} '{$competitorName}' does not meet match requirement: {$requirement}. Choose eligible competitor or modify match requirements."
        );
    }

    /**
     * Exception for tag team with insufficient active members.
     */
    public static function insufficientTagTeamMembers(Model $tagTeam, int $activeMembers, int $required): static
    {
        $teamName = $tagTeam->name ?? "ID: {$tagTeam->id}";

        return new self(
            "Tag team '{$teamName}' has only {$activeMembers} active members but requires {$required} for competition. Ensure sufficient active members or choose different team."
        );
    }

    /**
     * Exception for stable with insufficient active members.
     */
    public static function insufficientStableMembers(Model $stable, int $activeMembers, int $required): static
    {
        $stableName = $stable->name ?? "ID: {$stable->id}";

        return new self(
            "Stable '{$stableName}' has only {$activeMembers} active members but requires {$required} for this type of competition. Ensure sufficient active members or modify match requirements."
        );
    }

    /**
     * Exception for competitor age restrictions.
     */
    public static function ageRestriction(Model $competitor, int $age, int $minimumAge): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);

        return new self(
            "{$competitorType} '{$competitorName}' is {$age} years old but must be at least {$minimumAge} for this competition. Check age eligibility requirements."
        );
    }

    /**
     * Exception for competitor experience level requirements.
     */
    public static function experienceRequirement(Model $competitor, string $currentLevel, string $requiredLevel): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);

        return new self(
            "{$competitorType} '{$competitorName}' has {$currentLevel} experience level but {$requiredLevel} is required for this competition. Choose experienced competitor or modify requirements."
        );
    }

    /**
     * Exception for competitor championship eligibility.
     */
    public static function notEligibleForTitle(Model $competitor, Model $title, string $reason): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);
        $titleName = $title->name ?? "ID: {$title->id}";

        return new self(
            "{$competitorType} '{$competitorName}' is not eligible for championship '{$titleName}': {$reason}. Review title eligibility requirements."
        );
    }

    /**
     * Exception for competitor being current champion (can't compete for same title).
     */
    public static function currentChampion(Model $competitor, Model $title): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);
        $titleName = $title->name ?? "ID: {$title->id}";

        return new self(
            "{$competitorType} '{$competitorName}' is the current '{$titleName}' champion and cannot compete for the same title. Title matches require challenger competitors."
        );
    }

    /**
     * Exception for geographic or travel restrictions.
     */
    public static function travelRestriction(Model $competitor, Model $venue, string $restriction): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);
        $venueName = $venue->name ?? "ID: {$venue->id}";

        return new self(
            "{$competitorType} '{$competitorName}' cannot compete at venue '{$venueName}': {$restriction}. Choose different venue or resolve travel restrictions."
        );
    }

    /**
     * Exception for medical clearance requirements.
     */
    public static function medicalClearanceRequired(Model $competitor, string $requirement): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);

        return new self(
            "{$competitorType} '{$competitorName}' requires medical clearance: {$requirement}. Obtain clearance before booking or choose cleared competitor."
        );
    }

    /**
     * Exception for booking window violations.
     */
    public static function outsideBookingWindow(Model $competitor, Carbon $eventDate, int $requiredDaysNotice): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);
        $daysUntilEvent = now()->diffInDays($eventDate);

        return new self(
            "{$competitorType} '{$competitorName}' requires {$requiredDaysNotice} days notice but event is in {$daysUntilEvent} days. Book earlier or choose competitor with shorter notice requirements."
        );
    }
}
