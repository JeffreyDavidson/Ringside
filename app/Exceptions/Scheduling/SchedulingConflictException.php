<?php

declare(strict_types=1);

namespace App\Exceptions\Scheduling;

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Exception thrown when scheduling conflicts occur in wrestling promotion management.
 *
 * This exception handles various date, time, and resource scheduling conflicts
 * that occur during event planning, match booking, and resource allocation.
 *
 * BUSINESS CONTEXT:
 * Wrestling promotions must carefully manage scheduling to avoid conflicts between
 * events, competitor availability, venue bookings, and resource allocation.
 * This exception provides specific feedback for scheduling violations.
 *
 * COMMON SCENARIOS:
 * - Venue double-booking
 * - Competitor scheduling conflicts
 * - Event date conflicts
 * - Resource availability conflicts
 *
 * @example
 * ```php
 * // Venue conflict
 * throw SchedulingConflictException::venueConflict($venue, $existingEvent, $newEvent);
 *
 * // Date conflict
 * throw SchedulingConflictException::dateConflict($competitor, $date, $conflictingBooking);
 *
 * // Resource conflict
 * throw SchedulingConflictException::resourceConflict('ring equipment', $date, 'Championship Match');
 * ```
 */
class SchedulingConflictException extends BaseBusinessException
{
    /**
     * Exception for venue being double-booked.
     */
    public static function venueConflict(Model $venue, Model $existingEvent, Model $newEvent): static
    {
        $venueName = $venue->name ?? "ID: {$venue->id}";
        $existingEventName = $existingEvent->name ?? "ID: {$existingEvent->id}";
        $newEventName = $newEvent->name ?? "ID: {$newEvent->id}";

        return new self(
            "Venue '{$venueName}' is already booked for event '{$existingEventName}' and cannot be double-booked for '{$newEventName}'. Choose different venue or reschedule event."
        );
    }

    /**
     * Exception for date/time scheduling conflicts.
     */
    public static function dateConflict(Model $entity, Carbon $conflictDate, Model $conflictingBooking): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);
        $bookingName = $conflictingBooking->name ?? "ID: {$conflictingBooking->id}";

        return new self(
            "{$entityType} '{$entityName}' has existing booking '{$bookingName}' on {$conflictDate->format('Y-m-d H:i')}. Resolve scheduling conflict or choose different date/entity."
        );
    }

    /**
     * Exception for resource availability conflicts.
     */
    public static function resourceConflict(string $resource, Carbon $date, string $conflictingUse): static
    {
        return new self(
            "Resource '{$resource}' is not available on {$date->format('Y-m-d H:i')} due to '{$conflictingUse}'. Schedule for different time or secure additional resources."
        );
    }

    /**
     * Exception for event scheduling conflicts.
     */
    public static function eventConflict(Model $event1, Model $event2, string $conflictType): static
    {
        $event1Name = $event1->name ?? "ID: {$event1->id}";
        $event2Name = $event2->name ?? "ID: {$event2->id}";

        return new self(
            "Events '{$event1Name}' and '{$event2Name}' have {$conflictType} conflict. Adjust scheduling to prevent overlap."
        );
    }

    /**
     * Exception for competitor availability conflicts.
     */
    public static function competitorConflict(Model $competitor, Carbon $requestedDate, string $existingCommitment): static
    {
        $competitorName = $competitor->name ?? "ID: {$competitor->id}";
        $competitorType = class_basename($competitor);

        return new self(
            "{$competitorType} '{$competitorName}' is not available on {$requestedDate->format('Y-m-d')} due to existing commitment: {$existingCommitment}. Choose different date or competitor."
        );
    }

    /**
     * Exception for referee scheduling conflicts.
     */
    public static function refereeConflict(Model $referee, Carbon $matchDate, Model $conflictingMatch): static
    {
        $refereeName = $referee->name ?? "ID: {$referee->id}";
        $conflictingMatchId = $conflictingMatch->id ?? 'unknown';

        return new self(
            "Referee '{$refereeName}' is already assigned to match {$conflictingMatchId} on {$matchDate->format('Y-m-d H:i')}. Assign different referee or reschedule."
        );
    }

    /**
     * Exception for title scheduling conflicts.
     */
    public static function titleConflict(Model $title, Carbon $date, string $conflictingMatch): static
    {
        $titleName = $title->name ?? "ID: {$title->id}";

        return new self(
            "Championship '{$titleName}' is already scheduled for defense on {$date->format('Y-m-d')} in {$conflictingMatch}. Titles cannot be defended multiple times on same date."
        );
    }

    /**
     * Exception for broadcast/media scheduling conflicts.
     */
    public static function broadcastConflict(Carbon $date, string $existingBroadcast, string $newBroadcast): static
    {
        return new self(
            "Broadcast conflict on {$date->format('Y-m-d H:i')}: '{$existingBroadcast}' already scheduled, cannot add '{$newBroadcast}'. Coordinate broadcast scheduling."
        );
    }

    /**
     * Exception for seasonal/holiday scheduling restrictions.
     */
    public static function seasonalRestriction(Carbon $date, string $restriction): static
    {
        return new self(
            "Scheduling restriction on {$date->format('Y-m-d')}: {$restriction}. Choose unrestricted date or obtain special approval."
        );
    }

    /**
     * Exception for travel/logistics conflicts.
     */
    public static function travelConflict(Model $entity, Carbon $date, string $travelRequirement): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' has travel conflict on {$date->format('Y-m-d')}: {$travelRequirement}. Allow sufficient travel time or choose local entity."
        );
    }

    /**
     * Exception for minimum rest period violations.
     */
    public static function insufficientRest(Model $entity, Carbon $lastAppearance, int $requiredRestDays, int $actualRestDays): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' last appeared on {$lastAppearance->format('Y-m-d')} and requires {$requiredRestDays} days rest, but only {$actualRestDays} days provided. Allow sufficient recovery time."
        );
    }

    /**
     * Exception for contractual exclusivity periods.
     */
    public static function exclusivityPeriod(Model $entity, Carbon $startDate, Carbon $endDate, string $exclusiveCommitment): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' has exclusivity period from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} for '{$exclusiveCommitment}'. Schedule outside exclusivity period."
        );
    }

    /**
     * Exception for booking window violations.
     */
    public static function outsideBookingWindow(Carbon $eventDate, int $requiredDaysNotice, int $actualDaysNotice): static
    {
        return new self(
            "Event scheduled for {$eventDate->format('Y-m-d')} requires {$requiredDaysNotice} days notice but only {$actualDaysNotice} days provided. Book further in advance or request exception."
        );
    }

    /**
     * Exception for capacity/audience scheduling conflicts.
     */
    public static function capacityConflict(Model $venue, Carbon $date, int $requiredCapacity, int $availableCapacity): static
    {
        $venueName = $venue->name ?? "ID: {$venue->id}";

        return new self(
            "Venue '{$venueName}' on {$date->format('Y-m-d')} requires {$requiredCapacity} capacity but only {$availableCapacity} available. Choose larger venue or reduce requirements."
        );
    }

    /**
     * Exception for equipment/setup scheduling conflicts.
     */
    public static function equipmentConflict(string $equipment, Carbon $date, string $conflictingSetup): static
    {
        return new self(
            "Equipment '{$equipment}' is not available on {$date->format('Y-m-d')} due to '{$conflictingSetup}' setup requirements. Schedule for different time or secure additional equipment."
        );
    }

    /**
     * Exception for staff scheduling conflicts.
     */
    public static function staffConflict(string $staffRole, Carbon $date, string $conflictingAssignment): static
    {
        return new self(
            "{$staffRole} staff unavailable on {$date->format('Y-m-d')} due to '{$conflictingAssignment}'. Assign different staff or reschedule."
        );
    }

    /**
     * Exception for regulatory/permit scheduling conflicts.
     */
    public static function permitConflict(Model $venue, Carbon $date, string $permitIssue): static
    {
        $venueName = $venue->name ?? "ID: {$venue->id}";

        return new self(
            "Venue '{$venueName}' on {$date->format('Y-m-d')} has permit conflict: {$permitIssue}. Obtain proper permits or choose compliant venue/date."
        );
    }

    /**
     * Exception for overlapping tournament brackets.
     */
    public static function tournamentConflict(string $tournament1, string $tournament2, Carbon $overlapPeriod): static
    {
        return new self(
            "Tournaments '{$tournament1}' and '{$tournament2}' have overlapping schedules around {$overlapPeriod->format('Y-m-d')}. Stagger tournament dates to prevent conflicts."
        );
    }
}
