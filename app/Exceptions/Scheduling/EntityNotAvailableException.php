<?php

declare(strict_types=1);

namespace App\Exceptions\Scheduling;

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Exception thrown when an entity is not available for booking or operations due to business rule violations.
 *
 * This exception handles scenarios where entity availability is prevented by current state,
 * status conditions, or business logic constraints in wrestling promotion scheduling management.
 *
 * BUSINESS CONTEXT:
 * Wrestling entity availability represents the complex interplay of health status, employment
 * conditions, contractual obligations, and operational readiness. Entities (wrestlers, managers,
 * referees, tag teams, stables, titles) must meet multiple criteria to be considered available
 * for booking in matches, events, or storylines. This differs from simple scheduling conflicts
 * by focusing on inherent entity state rather than calendar conflicts.
 *
 * COMMON SCENARIOS:
 * - Attempting to book injured performers who cannot safely compete
 * - Trying to schedule suspended entities during disciplinary periods
 * - Booking retired legends who are no longer active competitors
 * - Scheduling unemployed talent without current promotion contracts
 * - Assigning inactive entities that haven't been properly activated
 * - Double-booking entities already committed to other events
 * - Booking entities without required medical clearances or certifications
 * - Scheduling entities outside their contractual availability windows
 *
 * BUSINESS IMPACT:
 * - Maintains performer safety and health compliance standards
 * - Protects contractual integrity and employment relationship clarity
 * - Ensures proper talent management and availability tracking
 * - Prevents booking conflicts that could disrupt event planning
 * - Maintains audience expectations for performer availability
 * - Protects against liability issues from inappropriate bookings
 */
final class EntityNotAvailableException extends BaseBusinessException
{
    /**
     * Entity is currently injured and cannot be booked for safety reasons.
     *
     * @param  Model  $entity  The injured entity that cannot be booked
     * @param  Carbon|null  $injuryDate  Optional date when the injury occurred
     */
    public static function injured(Model $entity, ?Carbon $injuryDate = null): static
    {
        $context = self::formatModelContext($entity);
        $injuryInfo = $injuryDate ? " since {$injuryDate->format('Y-m-d')}" : '';

        return new self(
            "{$context} is currently injured{$injuryInfo} and unavailable for booking. Wait for recovery or choose available entity."
        );
    }

    /**
     * Entity is currently suspended and cannot be booked during disciplinary period.
     *
     * @param  Model  $entity  The suspended entity that cannot be booked
     * @param  string|null  $reason  Optional reason for the suspension
     */
    public static function suspended(Model $entity, ?string $reason = null): static
    {
        $context = self::formatModelContext($entity);
        $reasonInfo = $reason ? " (Reason: {$reason})" : '';

        return new self(
            "{$context} is currently suspended{$reasonInfo} and unavailable for booking. Resolve suspension or choose available entity."
        );
    }

    /**
     * Entity is permanently retired and cannot be booked for active competition.
     *
     * @param  Model  $entity  The retired entity that cannot be booked
     * @param  Carbon|null  $retirementDate  Optional date when retirement occurred
     */
    public static function retired(Model $entity, ?Carbon $retirementDate = null): static
    {
        $context = self::formatModelContext($entity);
        $retirementInfo = $retirementDate ? " since {$retirementDate->format('Y-m-d')}" : '';

        return new self(
            "{$context} is retired{$retirementInfo} and unavailable for booking. Use active entities only."
        );
    }

    /**
     * Entity is not currently employed and cannot be booked without active contract.
     *
     * @param  Model  $entity  The unemployed entity that cannot be booked
     */
    public static function unemployed(Model $entity): static
    {
        $context = self::formatModelContext($entity);

        return new self(
            "{$context} is not currently employed and unavailable for booking. Employ entity first or choose employed entity."
        );
    }

    /**
     * Entity is currently inactive and cannot be booked until activated.
     *
     * @param  Model  $entity  The inactive entity that cannot be booked
     * @param  string|null  $reason  Optional reason for inactive status
     */
    public static function inactive(Model $entity, ?string $reason = null): static
    {
        $context = self::formatModelContext($entity);
        $reasonInfo = $reason ? " ({$reason})" : '';

        return new self(
            "{$context} is inactive{$reasonInfo} and unavailable for booking. Activate entity or choose active entity."
        );
    }

    /**
     * Entity has existing scheduling conflict preventing new booking.
     *
     * @param  Model  $entity  The entity with scheduling conflict
     * @param  Carbon  $requestedDate  The date being requested for booking
     * @param  string  $conflictDetails  Description of the existing conflict
     */
    public static function schedulingConflict(Model $entity, Carbon $requestedDate, string $conflictDetails): static
    {
        $context = self::formatModelContext($entity);

        return new self(
            "{$context} has scheduling conflict on {$requestedDate->format('Y-m-d')}: {$conflictDetails}. Choose different date or entity."
        );
    }

    /**
     * Entity is already booked and cannot be double-booked for the same time period.
     *
     * @param  Model  $entity  The entity that is already booked
     * @param  Model  $existingBooking  The existing booking preventing new assignment
     */
    public static function doubleBooked(Model $entity, Model $existingBooking): static
    {
        $context = self::formatModelContext($entity);
        $bookingContext = self::formatModelContext($existingBooking);

        return new self(
            "{$context} is already booked for {$bookingContext} and cannot be double-booked. Resolve conflict or choose available entity."
        );
    }

    /**
     * Entity does not meet required qualifications for booking.
     *
     * @param  Model  $entity  The entity that doesn't meet requirements
     * @param  string  $requirement  The specific requirement that isn't met
     */
    public static function doesNotMeetRequirements(Model $entity, string $requirement): static
    {
        $context = self::formatModelContext($entity);

        return new self(
            "{$context} does not meet booking requirement: {$requirement}. Choose qualified entity or modify requirements."
        );
    }

    /**
     * Entity is in wrong geographic location for the booking requirements.
     *
     * @param  Model  $entity  The entity in wrong location
     * @param  string  $requiredLocation  The location required for booking
     * @param  string  $currentLocation  The entity's current location
     */
    public static function wrongLocation(Model $entity, string $requiredLocation, string $currentLocation): static
    {
        $context = self::formatModelContext($entity);

        return new self(
            "{$context} is in {$currentLocation} but {$requiredLocation} is required. Entity must be in correct location for booking."
        );
    }

    /**
     * Entity has contractual restrictions preventing the requested booking.
     *
     * @param  Model  $entity  The entity with contractual restrictions
     * @param  string  $restriction  Description of the contractual limitation
     */
    public static function contractualRestriction(Model $entity, string $restriction): static
    {
        $context = self::formatModelContext($entity);

        return new self(
            "{$context} has contractual restrictions: {$restriction}. Review contract terms or negotiate modification."
        );
    }

    /**
     * Entity requires longer advance notice than provided for booking.
     *
     * @param  Model  $entity  The entity requiring more notice
     * @param  int  $requiredDays  Number of days notice required
     * @param  int  $actualDays  Number of days notice actually provided
     */
    public static function insufficientNotice(Model $entity, int $requiredDays, int $actualDays): static
    {
        $context = self::formatModelContext($entity);

        return new self(
            "{$context} requires {$requiredDays} days notice but only {$actualDays} days provided. Book earlier or choose entity with shorter notice requirements."
        );
    }

    /**
     * Entity exceeds maximum allowed bookings and cannot accept additional appearances.
     *
     * @param  Model  $entity  The over-booked entity
     * @param  int  $maxAppearances  Maximum number of allowed appearances
     * @param  int  $currentBookings  Current number of bookings
     */
    public static function overBooked(Model $entity, int $maxAppearances, int $currentBookings): static
    {
        $context = self::formatModelContext($entity);

        return new self(
            "{$context} is over-booked with {$currentBookings} appearances (maximum: {$maxAppearances}). Reduce bookings or choose less busy entity."
        );
    }

    /**
     * Entity requires medical clearance before being available for booking.
     *
     * @param  Model  $entity  The entity requiring medical clearance
     * @param  string  $requirement  Description of the medical clearance needed
     */
    public static function medicalClearanceRequired(Model $entity, string $requirement): static
    {
        $context = self::formatModelContext($entity);

        return new self(
            "{$context} requires medical clearance: {$requirement}. Obtain clearance before booking or choose cleared entity."
        );
    }

    /**
     * Entity is under temporary restriction and cannot be booked until restriction expires.
     *
     * @param  Model  $entity  The temporarily restricted entity
     * @param  Carbon  $restrictionEnd  Date when restriction expires
     * @param  string  $reason  Reason for the temporary restriction
     */
    public static function temporarilyRestricted(Model $entity, Carbon $restrictionEnd, string $reason): static
    {
        $context = self::formatModelContext($entity);

        return new self(
            "{$context} is temporarily restricted until {$restrictionEnd->format('Y-m-d')} ({$reason}). Wait for restriction to expire or choose unrestricted entity."
        );
    }

    /**
     * Entity has age-related restrictions preventing booking in certain contexts.
     *
     * @param  Model  $entity  The entity with age restrictions
     * @param  int  $age  The entity's current age
     * @param  string  $restriction  Description of the age-related restriction
     */
    public static function ageRestriction(Model $entity, int $age, string $restriction): static
    {
        $context = self::formatModelContext($entity);

        return new self(
            "{$context} (age: {$age}) has age restriction: {$restriction}. Choose age-appropriate entity or modify booking requirements."
        );
    }

    /**
     * Entity requires special accommodations that are not currently available.
     *
     * @param  Model  $entity  The entity requiring special accommodations
     * @param  string  $accommodation  Description of the required accommodation
     */
    public static function specialAccommodationRequired(Model $entity, string $accommodation): static
    {
        $context = self::formatModelContext($entity);

        return new self(
            "{$context} requires special accommodation: {$accommodation}. Ensure accommodation is available or choose different entity."
        );
    }

    /**
     * Entity is unavailable due to personal reasons and cannot be booked.
     *
     * @param  Model  $entity  The entity unavailable for personal reasons
     * @param  string|null  $details  Optional details about the personal circumstances
     */
    public static function personalReasons(Model $entity, ?string $details = null): static
    {
        $context = self::formatModelContext($entity);
        $detailsInfo = $details ? " ({$details})" : '';

        return new self(
            "{$context} is unavailable due to personal reasons{$detailsInfo}. Respect personal time or choose available entity."
        );
    }

    /**
     * Entity has external commitments preventing availability for booking.
     *
     * @param  Model  $entity  The entity with external commitments
     * @param  string  $commitment  Description of the external commitment
     */
    public static function externalCommitments(Model $entity, string $commitment): static
    {
        $context = self::formatModelContext($entity);

        return new self(
            "{$context} has external commitment: {$commitment}. Schedule around commitment or choose available entity."
        );
    }
}
