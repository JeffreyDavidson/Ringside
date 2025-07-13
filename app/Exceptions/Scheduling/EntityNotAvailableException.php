<?php

declare(strict_types=1);

namespace App\Exceptions\Scheduling;

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Exception thrown when an entity is not available for booking or operations.
 *
 * This exception handles various entity availability scenarios in wrestling
 * promotion management, including status-based unavailability, scheduling
 * conflicts, and business rule restrictions.
 *
 * BUSINESS CONTEXT:
 * Wrestling entities (wrestlers, managers, referees, tag teams, stables, titles)
 * have complex availability rules based on their current status, health,
 * employment, and scheduling. This exception provides specific feedback for
 * availability violations.
 *
 * COMMON SCENARIOS:
 * - Injured entities cannot participate
 * - Suspended entities are restricted
 * - Retired entities are unavailable
 * - Unemployed entities cannot be booked
 * - Scheduling conflicts with existing bookings
 *
 * @example
 * ```php
 * // Injured wrestler
 * throw EntityNotAvailableException::injured($wrestler, $injuryDate);
 *
 * // Suspended entity
 * throw EntityNotAvailableException::suspended($tagTeam, 'conduct violation');
 *
 * // Retired entity
 * throw EntityNotAvailableException::retired($title, $retirementDate);
 * ```
 */
class EntityNotAvailableException extends BaseBusinessException
{
    /**
     * Exception for injured entity being unavailable.
     */
    public static function injured(Model $entity, ?Carbon $injuryDate = null): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);
        $injuryInfo = $injuryDate ? " since {$injuryDate->format('Y-m-d')}" : '';

        return new self(
            "{$entityType} '{$entityName}' is currently injured{$injuryInfo} and unavailable for booking. Wait for recovery or choose available entity."
        );
    }

    /**
     * Exception for suspended entity being unavailable.
     */
    public static function suspended(Model $entity, ?string $reason = null): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);
        $reasonInfo = $reason ? " (Reason: {$reason})" : '';

        return new self(
            "{$entityType} '{$entityName}' is currently suspended{$reasonInfo} and unavailable for booking. Resolve suspension or choose available entity."
        );
    }

    /**
     * Exception for retired entity being unavailable.
     */
    public static function retired(Model $entity, ?Carbon $retirementDate = null): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);
        $retirementInfo = $retirementDate ? " since {$retirementDate->format('Y-m-d')}" : '';

        return new self(
            "{$entityType} '{$entityName}' is retired{$retirementInfo} and unavailable for booking. Use active entities only."
        );
    }

    /**
     * Exception for unemployed entity being unavailable.
     */
    public static function unemployed(Model $entity): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' is not currently employed and unavailable for booking. Employ entity first or choose employed entity."
        );
    }

    /**
     * Exception for inactive entity being unavailable.
     */
    public static function inactive(Model $entity, ?string $reason = null): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);
        $reasonInfo = $reason ? " ({$reason})" : '';

        return new self(
            "{$entityType} '{$entityName}' is inactive{$reasonInfo} and unavailable for booking. Activate entity or choose active entity."
        );
    }

    /**
     * Exception for entity having scheduling conflicts.
     */
    public static function schedulingConflict(Model $entity, Carbon $requestedDate, string $conflictDetails): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' has scheduling conflict on {$requestedDate->format('Y-m-d')}: {$conflictDetails}. Choose different date or entity."
        );
    }

    /**
     * Exception for entity being double-booked.
     */
    public static function doubleBooked(Model $entity, Model $existingBooking): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);
        $bookingId = $existingBooking->id ?? 'unknown';

        return new self(
            "{$entityType} '{$entityName}' is already booked for event/match {$bookingId} and cannot be double-booked. Resolve conflict or choose available entity."
        );
    }

    /**
     * Exception for entity not meeting booking requirements.
     */
    public static function doesNotMeetRequirements(Model $entity, string $requirement): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' does not meet booking requirement: {$requirement}. Choose qualified entity or modify requirements."
        );
    }

    /**
     * Exception for entity being in wrong location/territory.
     */
    public static function wrongLocation(Model $entity, string $requiredLocation, string $currentLocation): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' is in {$currentLocation} but {$requiredLocation} is required. Entity must be in correct location for booking."
        );
    }

    /**
     * Exception for entity having contractual restrictions.
     */
    public static function contractualRestriction(Model $entity, string $restriction): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' has contractual restrictions: {$restriction}. Review contract terms or negotiate modification."
        );
    }

    /**
     * Exception for entity requiring specific notice period.
     */
    public static function insufficientNotice(Model $entity, int $requiredDays, int $actualDays): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' requires {$requiredDays} days notice but only {$actualDays} days provided. Book earlier or choose entity with shorter notice requirements."
        );
    }

    /**
     * Exception for entity being over-booked (too many appearances).
     */
    public static function overBooked(Model $entity, int $maxAppearances, int $currentBookings): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' is over-booked with {$currentBookings} appearances (maximum: {$maxAppearances}). Reduce bookings or choose less busy entity."
        );
    }

    /**
     * Exception for medical clearance being required.
     */
    public static function medicalClearanceRequired(Model $entity, string $requirement): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' requires medical clearance: {$requirement}. Obtain clearance before booking or choose cleared entity."
        );
    }

    /**
     * Exception for entity being temporarily restricted.
     */
    public static function temporarilyRestricted(Model $entity, Carbon $restrictionEnd, string $reason): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' is temporarily restricted until {$restrictionEnd->format('Y-m-d')} ({$reason}). Wait for restriction to expire or choose unrestricted entity."
        );
    }

    /**
     * Exception for age-related availability restrictions.
     */
    public static function ageRestriction(Model $entity, int $age, string $restriction): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' (age: {$age}) has age restriction: {$restriction}. Choose age-appropriate entity or modify booking requirements."
        );
    }

    /**
     * Exception for entity requiring special accommodations.
     */
    public static function specialAccommodationRequired(Model $entity, string $accommodation): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' requires special accommodation: {$accommodation}. Ensure accommodation is available or choose different entity."
        );
    }

    /**
     * Exception for entity being unavailable due to personal reasons.
     */
    public static function personalReasons(Model $entity, ?string $details = null): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);
        $detailsInfo = $details ? " ({$details})" : '';

        return new self(
            "{$entityType} '{$entityName}' is unavailable due to personal reasons{$detailsInfo}. Respect personal time or choose available entity."
        );
    }

    /**
     * Exception for entity being unavailable due to external commitments.
     */
    public static function externalCommitments(Model $entity, string $commitment): static
    {
        $entityName = $entity->name ?? "ID: {$entity->id}";
        $entityType = class_basename($entity);

        return new self(
            "{$entityType} '{$entityName}' has external commitment: {$commitment}. Schedule around commitment or choose available entity."
        );
    }
}
