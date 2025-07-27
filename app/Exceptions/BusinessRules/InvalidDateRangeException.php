<?php

declare(strict_types=1);

namespace App\Exceptions\BusinessRules;

use App\Exceptions\BaseBusinessException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Exception thrown when date range validation fails in wrestling promotion management.
 *
 * This exception handles comprehensive date range validation scenarios that occur
 * across all time-sensitive business operations, providing contextual feedback
 * for temporal constraint violations and business rule compliance.
 *
 * BUSINESS CONTEXT:
 * Wrestling promotions operate on complex temporal frameworks involving employment
 * contracts, injury recovery periods, suspension terms, championship reigns, event
 * scheduling, and career lifecycle management. Date range validation ensures
 * operational integrity, regulatory compliance, and logical consistency across
 * all time-based business processes.
 *
 * COMMON SCENARIOS:
 * - Employment period conflicts with existing contracts or retirement status
 * - Injury durations that overlap with scheduled performances or championship defenses
 * - Suspension periods that conflict with contractual obligations or tournament participation
 * - Championship reign dates that violate title lineage continuity
 * - Event scheduling that conflicts with venue availability or regulatory restrictions
 * - Career milestone dates that don't align with documented wrestling history
 * - Contract renewal periods that exceed industry standards or promotional budgets
 * - Age-based eligibility violations for specific divisions or championship categories
 *
 * BUSINESS IMPACT:
 * - Maintains data integrity and operational consistency across all time-sensitive processes
 * - Protects championship lineages, career records, and historical accuracy
 * - Ensures regulatory compliance with athletic commissions and industry standards
 * - Prevents scheduling conflicts that could result in financial losses or legal disputes
 * - Safeguards against fraudulent backdating or timeline manipulation
 * - Supports accurate reporting, analytics, and business intelligence initiatives
 */
final class InvalidDateRangeException extends BaseBusinessException
{
    /**
     * End date occurs before start date, violating logical date ordering.
     *
     * @param  Carbon  $startDate  The start date that occurs after the end date
     * @param  Carbon  $endDate  The end date that occurs before the start date
     * @param  string|null  $context  Optional context for the date range validation
     */
    public static function endBeforeStart(Carbon $startDate, Carbon $endDate, ?string $context = null): static
    {
        $contextInfo = $context ? " for {$context}" : '';

        return new self(
            "Invalid date range{$contextInfo}: end date ({$endDate->format('Y-m-d')}) cannot be before start date ({$startDate->format('Y-m-d')}). Ensure logical date ordering."
        );
    }

    /**
     * Date range violates established business rules for the operation.
     *
     * @param  Carbon  $startDate  Start date of the invalid range
     * @param  Carbon  $endDate  End date of the invalid range
     * @param  string  $rule  The specific business rule that was violated
     */
    public static function violatesBusinessRule(Carbon $startDate, Carbon $endDate, string $rule): static
    {
        $duration = $startDate->diffInDays($endDate);

        return new static(
            "Date range from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} ({$duration} days) violates business rule: {$rule}"
        );
    }

    /**
     * Date range duration is shorter than the required minimum.
     *
     * @param  Carbon  $startDate  Start date of the short range
     * @param  Carbon  $endDate  End date of the short range
     * @param  int  $minimumDays  Minimum required duration in days
     * @param  string  $context  Context describing what requires the minimum duration
     */
    public static function tooShort(Carbon $startDate, Carbon $endDate, int $minimumDays, string $context): static
    {
        $actualDays = $startDate->diffInDays($endDate);

        return new static(
            "{$context} period from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} is too short ({$actualDays} days). Minimum required: {$minimumDays} days."
        );
    }

    /**
     * Date range duration exceeds the allowed maximum.
     *
     * @param  Carbon  $startDate  Start date of the long range
     * @param  Carbon  $endDate  End date of the long range
     * @param  int  $maximumDays  Maximum allowed duration in days
     * @param  string  $context  Context describing what limits the maximum duration
     */
    public static function tooLong(Carbon $startDate, Carbon $endDate, int $maximumDays, string $context): static
    {
        $actualDays = $startDate->diffInDays($endDate);

        return new static(
            "{$context} period from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} is too long ({$actualDays} days). Maximum allowed: {$maximumDays} days."
        );
    }

    /**
     * Date range overlaps with an existing period, creating a conflict.
     *
     * @param  array{start: Carbon, end: Carbon}  $newPeriod  The new period causing the overlap
     * @param  array{start: Carbon, end: Carbon}  $existingPeriod  The existing period being overlapped
     * @param  string  $type  Type of period that cannot overlap
     */
    public static function overlapsExisting(array $newPeriod, array $existingPeriod, string $type): static
    {
        $newStart = $newPeriod['start']->format('Y-m-d');
        $newEnd = $newPeriod['end']->format('Y-m-d');
        $existingStart = $existingPeriod['start']->format('Y-m-d');
        $existingEnd = $existingPeriod['end']->format('Y-m-d');

        return new static(
            "New {$type} period ({$newStart} to {$newEnd}) overlaps with existing period ({$existingStart} to {$existingEnd}). Periods cannot overlap."
        );
    }

    /**
     * Date is in the future when past or present date is required.
     *
     * @param  Carbon  $date  The future date that is not allowed
     * @param  string  $context  Context describing why future dates are not allowed
     */
    public static function futureNotAllowed(Carbon $date, string $context): static
    {
        return new static(
            "{$context} date ({$date->format('Y-m-d')}) cannot be in the future. Use current or past date only."
        );
    }

    /**
     * Date is in the past when present or future date is required.
     *
     * @param  Carbon  $date  The past date that is not allowed
     * @param  string  $context  Context describing why past dates are not allowed
     */
    public static function pastNotAllowed(Carbon $date, string $context): static
    {
        return new static(
            "{$context} date ({$date->format('Y-m-d')}) cannot be in the past. Use current or future date only."
        );
    }

    /**
     * Date range does not align with business calendar requirements.
     *
     * @param  Carbon  $startDate  Start date of the misaligned range
     * @param  Carbon  $endDate  End date of the misaligned range
     * @param  string  $requirement  The specific calendar requirement that was not met
     */
    public static function notAlignedWithBusinessCalendar(Carbon $startDate, Carbon $endDate, string $requirement): static
    {
        return new static(
            "Date range from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} does not align with business calendar requirement: {$requirement}"
        );
    }

    /**
     * Employment period validation fails for wrestler or staff member.
     *
     * @param  Model  $entity  The wrestler or staff member with invalid employment period
     * @param  Carbon  $startDate  Employment start date
     * @param  Carbon  $endDate  Employment end date
     * @param  string  $reason  Specific reason for validation failure
     */
    public static function invalidEmploymentPeriod(Model $entity, Carbon $startDate, Carbon $endDate, string $reason): static
    {
        $context = self::formatModelContext($entity);

        return new static(
            "{$context} has invalid employment period from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}: {$reason}."
        );
    }

    /**
     * Injury period validation fails for wrestler or referee.
     *
     * @param  Model  $entity  The wrestler or referee with invalid injury period
     * @param  Carbon  $startDate  Injury start date
     * @param  Carbon  $endDate  Injury end date
     * @param  string  $reason  Specific reason for validation failure
     */
    public static function invalidInjuryPeriod(Model $entity, Carbon $startDate, Carbon $endDate, string $reason): static
    {
        $context = self::formatModelContext($entity);

        return new static(
            "{$context} has invalid injury period from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}: {$reason}."
        );
    }

    /**
     * Suspension period validation fails for wrestler, referee, or manager.
     *
     * @param  Model  $entity  The entity with invalid suspension period
     * @param  Carbon  $startDate  Suspension start date
     * @param  Carbon  $endDate  Suspension end date
     * @param  string  $reason  Specific reason for validation failure
     */
    public static function invalidSuspensionPeriod(Model $entity, Carbon $startDate, Carbon $endDate, string $reason): static
    {
        $context = self::formatModelContext($entity);

        return new static(
            "{$context} has invalid suspension period from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}: {$reason}."
        );
    }

    /**
     * Championship reign period validation fails for title holder.
     *
     * @param  Model  $title  The championship title with invalid reign period
     * @param  Carbon  $startDate  Championship reign start date
     * @param  Carbon  $endDate  Championship reign end date
     * @param  string  $reason  Specific reason for validation failure
     */
    public static function invalidChampionshipReign(Model $title, Carbon $startDate, Carbon $endDate, string $reason): static
    {
        $context = self::formatModelContext($title);

        return new static(
            "{$context} has invalid championship reign period from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}: {$reason}."
        );
    }

    /**
     * Event date validation fails for scheduled event.
     *
     * @param  Model  $event  The event with invalid date
     * @param  Carbon  $eventDate  The invalid event date
     * @param  string  $reason  Specific reason for validation failure
     */
    public static function invalidEventDate(Model $event, Carbon $eventDate, string $reason): static
    {
        $context = self::formatModelContext($event);

        return new static(
            "{$context} has invalid event date ({$eventDate->format('Y-m-d')}): {$reason}."
        );
    }

    /**
     * Contract period fails validation requirements for the contract type.
     *
     * @param  Carbon  $startDate  Start date of the invalid contract period
     * @param  Carbon  $endDate  End date of the invalid contract period
     * @param  string  $contractType  Type of contract with invalid period
     */
    public static function invalidContractPeriod(Carbon $startDate, Carbon $endDate, string $contractType): static
    {
        $duration = $startDate->diffInDays($endDate);

        return new static(
            "Invalid {$contractType} contract period from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} ({$duration} days). Check contract terms and business rules."
        );
    }

    /**
     * Age restriction violation for wrestler or staff member.
     *
     * @param  Model  $entity  The entity with age restriction violation
     * @param  Carbon  $birthDate  Entity's birth date
     * @param  Carbon  $eventDate  Date of the restricted event
     * @param  int  $minimumAge  Required minimum age
     */
    public static function ageRestrictionViolation(Model $entity, Carbon $birthDate, Carbon $eventDate, int $minimumAge): static
    {
        $context = self::formatModelContext($entity);
        $ageAtEvent = $birthDate->diffInYears($eventDate);

        return new static(
            "{$context} has age restriction violation: born on {$birthDate->format('Y-m-d')}, would be {$ageAtEvent} years old on {$eventDate->format('Y-m-d')}, but minimum age is {$minimumAge}."
        );
    }

    /**
     * Date violates seasonal or holiday restrictions for the operation.
     *
     * @param  Carbon  $date  The date that violates seasonal restrictions
     * @param  string  $restriction  The specific seasonal restriction that was violated
     */
    public static function seasonalRestriction(Carbon $date, string $restriction): static
    {
        return new static(
            "Date {$date->format('Y-m-d')} violates seasonal restriction: {$restriction}"
        );
    }

    /**
     * Gap between date periods does not meet minimum requirements.
     *
     * @param  Carbon  $endDate1  End date of the first period
     * @param  Carbon  $startDate2  Start date of the second period
     * @param  int  $requiredGapDays  Required number of days between periods
     * @param  string  $context  Context describing what requires the gap
     */
    public static function invalidGap(Carbon $endDate1, Carbon $startDate2, int $requiredGapDays, string $context): static
    {
        $actualGap = $endDate1->diffInDays($startDate2);

        return new static(
            "Invalid gap in {$context}: {$actualGap} days between {$endDate1->format('Y-m-d')} and {$startDate2->format('Y-m-d')}. Required gap: {$requiredGapDays} days."
        );
    }

    /**
     * Date is too far in the past, exceeding retroactive date limits.
     *
     * @param  Carbon  $date  The retroactive date that exceeds limits
     * @param  string  $context  Context describing the retroactive operation
     * @param  int  $maxRetroactiveDays  Maximum number of retroactive days allowed
     */
    public static function retroactiveNotAllowed(Carbon $date, string $context, int $maxRetroactiveDays): static
    {
        $daysBack = now()->diffInDays($date);

        return new static(
            "Retroactive {$context} date ({$date->format('Y-m-d')}) is {$daysBack} days in the past. Maximum retroactive period: {$maxRetroactiveDays} days."
        );
    }

    /**
     * Date falls on weekend when business day is required.
     *
     * @param  Carbon  $date  The weekend date that is not allowed
     * @param  string  $context  Context describing why business days are required
     */
    public static function businessDayRequired(Carbon $date, string $context): static
    {
        $dayName = $date->format('l');

        return new static(
            "{$context} date ({$date->format('Y-m-d')}) falls on {$dayName}. Business days (Monday-Friday) required."
        );
    }

    /**
     * Date range crosses fiscal year boundaries when periods must stay within fiscal years.
     *
     * @param  Carbon  $startDate  Start date of the range crossing fiscal years
     * @param  Carbon  $endDate  End date of the range crossing fiscal years
     * @param  string  $fiscalYearEnd  The fiscal year end date that was crossed
     */
    public static function crossesFiscalYear(Carbon $startDate, Carbon $endDate, string $fiscalYearEnd): static
    {
        return new static(
            "Date range from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} crosses fiscal year boundary ({$fiscalYearEnd}). Periods cannot span fiscal years."
        );
    }
}
