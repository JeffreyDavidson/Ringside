<?php

declare(strict_types=1);

namespace App\Exceptions\Scheduling;

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Exception thrown when scheduling conflicts occur in wrestling promotion management due to business rule violations.
 *
 * This exception handles scenarios where scheduling operations are prevented by overlapping
 * commitments, resource conflicts, or temporal business logic constraints in event planning.
 *
 * BUSINESS CONTEXT:
 * Wrestling promotion scheduling represents the complex orchestration of venues, talent,
 * equipment, staff, and broadcast resources across multiple events and timeframes. Effective
 * scheduling prevents conflicts that could result in operational failures, contract violations,
 * or audience disappointment. This differs from entity availability by focusing on temporal
 * and resource conflicts rather than individual entity status.
 *
 * COMMON SCENARIOS:
 * - Attempting to book venues already committed to other events
 * - Scheduling competitors with existing performance obligations
 * - Double-booking critical resources like championship titles or specialized equipment
 * - Creating overlapping broadcast windows or media commitments
 * - Violating minimum rest periods between performances
 * - Scheduling during contractual exclusivity periods
 * - Booking outside permitted operational windows
 * - Creating equipment or staffing conflicts between simultaneous events
 *
 * BUSINESS IMPACT:
 * - Prevents operational failures from resource conflicts
 * - Maintains contractual compliance for talent and venue agreements
 * - Protects broadcast and media relationship integrity
 * - Ensures performer safety through adequate rest periods
 * - Maintains audience trust through reliable event delivery
 * - Prevents financial losses from scheduling mishaps
 */
final class SchedulingConflictException extends BaseBusinessException
{
    /**
     * Venue is already booked and cannot be double-booked for conflicting events.
     *
     * @param  Model  $venue  The venue with booking conflict
     * @param  Model  $existingEvent  The existing event using the venue
     * @param  Model  $newEvent  The new event attempting to book the venue
     */
    public static function venueConflict(Model $venue, Model $existingEvent, Model $newEvent): static
    {
        $venueContext = self::formatModelContext($venue);
        $existingContext = self::formatModelContext($existingEvent);
        $newContext = self::formatModelContext($newEvent);

        return new self(
            "{$venueContext} is already booked for {$existingContext} and cannot be double-booked for {$newContext}. Choose different venue or reschedule event."
        );
    }

    /**
     * Entity has existing booking creating date/time scheduling conflict.
     *
     * @param  Model  $entity  The entity with date conflict
     * @param  Carbon  $conflictDate  The date with scheduling conflict
     * @param  Model  $conflictingBooking  The existing booking causing conflict
     */
    public static function dateConflict(Model $entity, Carbon $conflictDate, Model $conflictingBooking): static
    {
        $entityContext = self::formatModelContext($entity);
        $bookingContext = self::formatModelContext($conflictingBooking);

        return new self(
            "{$entityContext} has existing {$bookingContext} on {$conflictDate->format('Y-m-d H:i')}. Resolve scheduling conflict or choose different date/entity."
        );
    }

    /**
     * Critical resource is unavailable due to conflicting usage.
     *
     * @param  string  $resource  The resource with availability conflict
     * @param  Carbon  $date  The date when resource is needed
     * @param  string  $conflictingUse  Description of conflicting resource usage
     */
    public static function resourceConflict(string $resource, Carbon $date, string $conflictingUse): static
    {
        return new self(
            "Resource '{$resource}' is not available on {$date->format('Y-m-d H:i')} due to '{$conflictingUse}'. Schedule for different time or secure additional resources."
        );
    }

    /**
     * Events have overlapping scheduling creating operational conflict.
     *
     * @param  Model  $event1  The first event in conflict
     * @param  Model  $event2  The second event in conflict
     * @param  string  $conflictType  Description of the type of conflict
     */
    public static function eventConflict(Model $event1, Model $event2, string $conflictType): static
    {
        $event1Context = self::formatModelContext($event1);
        $event2Context = self::formatModelContext($event2);

        return new self(
            "{$event1Context} and {$event2Context} have {$conflictType} conflict. Adjust scheduling to prevent overlap."
        );
    }

    /**
     * Competitor has existing commitment preventing availability on requested date.
     *
     * @param  Model  $competitor  The competitor with scheduling conflict
     * @param  Carbon  $requestedDate  The date being requested for booking
     * @param  string  $existingCommitment  Description of existing commitment
     */
    public static function competitorConflict(Model $competitor, Carbon $requestedDate, string $existingCommitment): static
    {
        $context = self::formatModelContext($competitor);

        return new self(
            "{$context} is not available on {$requestedDate->format('Y-m-d')} due to existing commitment: {$existingCommitment}. Choose different date or competitor."
        );
    }

    /**
     * Referee is already assigned to another match preventing double-booking.
     *
     * @param  Model  $referee  The referee with scheduling conflict
     * @param  Carbon  $matchDate  The date of match requiring referee
     * @param  Model  $conflictingMatch  The existing match assignment
     */
    public static function refereeConflict(Model $referee, Carbon $matchDate, Model $conflictingMatch): static
    {
        $refereeContext = self::formatModelContext($referee);
        $matchContext = self::formatModelContext($conflictingMatch);

        return new self(
            "{$refereeContext} is already assigned to {$matchContext} on {$matchDate->format('Y-m-d H:i')}. Assign different referee or reschedule."
        );
    }

    /**
     * Championship title is already scheduled preventing multiple defenses on same date.
     *
     * @param  Model  $title  The title with scheduling conflict
     * @param  Carbon  $date  The date with existing title defense
     * @param  string  $conflictingMatch  Description of existing title defense
     */
    public static function titleConflict(Model $title, Carbon $date, string $conflictingMatch): static
    {
        $context = self::formatModelContext($title);

        return new self(
            "{$context} is already scheduled for defense on {$date->format('Y-m-d')} in {$conflictingMatch}. Titles cannot be defended multiple times on same date."
        );
    }

    /**
     * Broadcast time slots conflict preventing multiple simultaneous productions.
     *
     * @param  Carbon  $date  The date with broadcast conflict
     * @param  string  $existingBroadcast  Description of existing broadcast
     * @param  string  $newBroadcast  Description of new broadcast being scheduled
     */
    public static function broadcastConflict(Carbon $date, string $existingBroadcast, string $newBroadcast): static
    {
        return new self(
            "Broadcast conflict on {$date->format('Y-m-d H:i')}: '{$existingBroadcast}' already scheduled, cannot add '{$newBroadcast}'. Coordinate broadcast scheduling."
        );
    }

    /**
     * Date falls within seasonal or holiday restriction period.
     *
     * @param  Carbon  $date  The restricted date
     * @param  string  $restriction  Description of seasonal restriction
     */
    public static function seasonalRestriction(Carbon $date, string $restriction): static
    {
        return new self(
            "Scheduling restriction on {$date->format('Y-m-d')}: {$restriction}. Choose unrestricted date or obtain special approval."
        );
    }

    /**
     * Entity has travel or logistics conflict preventing timely arrival.
     *
     * @param  Model  $entity  The entity with travel conflict
     * @param  Carbon  $date  The date with travel scheduling issue
     * @param  string  $travelRequirement  Description of travel constraint
     */
    public static function travelConflict(Model $entity, Carbon $date, string $travelRequirement): static
    {
        $context = self::formatModelContext($entity);

        return new self(
            "{$context} has travel conflict on {$date->format('Y-m-d')}: {$travelRequirement}. Allow sufficient travel time or choose local entity."
        );
    }

    /**
     * Entity violates minimum rest period requirements between appearances.
     *
     * @param  Model  $entity  The entity requiring more rest time
     * @param  Carbon  $lastAppearance  Date of entity's last appearance
     * @param  int  $requiredRestDays  Number of rest days required
     * @param  int  $actualRestDays  Number of rest days actually provided
     */
    public static function insufficientRest(Model $entity, Carbon $lastAppearance, int $requiredRestDays, int $actualRestDays): static
    {
        $context = self::formatModelContext($entity);

        return new self(
            "{$context} last appeared on {$lastAppearance->format('Y-m-d')} and requires {$requiredRestDays} days rest, but only {$actualRestDays} days provided. Allow sufficient recovery time."
        );
    }

    /**
     * Entity is in contractual exclusivity period preventing other bookings.
     *
     * @param  Model  $entity  The entity with exclusivity restriction
     * @param  Carbon  $startDate  Start date of exclusivity period
     * @param  Carbon  $endDate  End date of exclusivity period
     * @param  string  $exclusiveCommitment  Description of exclusive commitment
     */
    public static function exclusivityPeriod(Model $entity, Carbon $startDate, Carbon $endDate, string $exclusiveCommitment): static
    {
        $context = self::formatModelContext($entity);

        return new self(
            "{$context} has exclusivity period from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} for '{$exclusiveCommitment}'. Schedule outside exclusivity period."
        );
    }

    /**
     * Event scheduling violates required advance booking window.
     *
     * @param  Carbon  $eventDate  The date of the event being scheduled
     * @param  int  $requiredDaysNotice  Number of days notice required
     * @param  int  $actualDaysNotice  Number of days notice actually provided
     */
    public static function outsideBookingWindow(Carbon $eventDate, int $requiredDaysNotice, int $actualDaysNotice): static
    {
        return new self(
            "Event scheduled for {$eventDate->format('Y-m-d')} requires {$requiredDaysNotice} days notice but only {$actualDaysNotice} days provided. Book further in advance or request exception."
        );
    }

    /**
     * Venue capacity is insufficient for event requirements on requested date.
     *
     * @param  Model  $venue  The venue with capacity limitations
     * @param  Carbon  $date  The date with capacity conflict
     * @param  int  $requiredCapacity  Required seating capacity
     * @param  int  $availableCapacity  Available seating capacity
     */
    public static function capacityConflict(Model $venue, Carbon $date, int $requiredCapacity, int $availableCapacity): static
    {
        $context = self::formatModelContext($venue);

        return new self(
            "{$context} on {$date->format('Y-m-d')} requires {$requiredCapacity} capacity but only {$availableCapacity} available. Choose larger venue or reduce requirements."
        );
    }

    /**
     * Required equipment is unavailable due to conflicting setup requirements.
     *
     * @param  string  $equipment  The equipment with scheduling conflict
     * @param  Carbon  $date  The date when equipment is needed
     * @param  string  $conflictingSetup  Description of conflicting setup
     */
    public static function equipmentConflict(string $equipment, Carbon $date, string $conflictingSetup): static
    {
        return new self(
            "Equipment '{$equipment}' is not available on {$date->format('Y-m-d')} due to '{$conflictingSetup}' setup requirements. Schedule for different time or secure additional equipment."
        );
    }

    /**
     * Required staff role is unavailable due to conflicting assignment.
     *
     * @param  string  $staffRole  The staff role with scheduling conflict
     * @param  Carbon  $date  The date when staff is needed
     * @param  string  $conflictingAssignment  Description of conflicting assignment
     */
    public static function staffConflict(string $staffRole, Carbon $date, string $conflictingAssignment): static
    {
        return new self(
            "{$staffRole} staff unavailable on {$date->format('Y-m-d')} due to '{$conflictingAssignment}'. Assign different staff or reschedule."
        );
    }

    /**
     * Venue has regulatory or permit conflicts preventing event scheduling.
     *
     * @param  Model  $venue  The venue with permit issues
     * @param  Carbon  $date  The date with permit conflict
     * @param  string  $permitIssue  Description of permit or regulatory issue
     */
    public static function permitConflict(Model $venue, Carbon $date, string $permitIssue): static
    {
        $context = self::formatModelContext($venue);

        return new self(
            "{$context} on {$date->format('Y-m-d')} has permit conflict: {$permitIssue}. Obtain proper permits or choose compliant venue/date."
        );
    }

    /**
     * Tournament schedules overlap creating participant and resource conflicts.
     *
     * @param  string  $tournament1  Name of first tournament
     * @param  string  $tournament2  Name of second tournament
     * @param  Carbon  $overlapPeriod  Date when tournaments overlap
     */
    public static function tournamentConflict(string $tournament1, string $tournament2, Carbon $overlapPeriod): static
    {
        return new self(
            "Tournaments '{$tournament1}' and '{$tournament2}' have overlapping schedules around {$overlapPeriod->format('Y-m-d')}. Stagger tournament dates to prevent conflicts."
        );
    }
}
