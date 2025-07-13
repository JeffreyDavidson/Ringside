<?php

declare(strict_types=1);

namespace App\Exceptions\BusinessRules;

use App\Exceptions\BaseBusinessException;
use Illuminate\Support\Carbon;

/**
 * Exception thrown when date range validation fails.
 *
 * This exception handles various date range validation scenarios in wrestling
 * promotion management, including logical date ordering, business rule
 * compliance, and temporal constraint violations.
 *
 * BUSINESS CONTEXT:
 * Wrestling promotions deal with many time-based entities including employment
 * periods, injury durations, suspension terms, championship reigns, and event
 * scheduling. Proper date range validation is critical for data integrity.
 *
 * COMMON SCENARIOS:
 * - End date before start date
 * - Date ranges outside allowed periods
 * - Overlapping restricted periods
 * - Invalid future/past date constraints
 *
 * @example
 * ```php
 * // Invalid date order
 * throw InvalidDateRangeException::endBeforeStart($startDate, $endDate);
 *
 * // Business rule violation
 * throw InvalidDateRangeException::violatesBusinessRule($start, $end, 'Employment periods cannot exceed 5 years');
 *
 * // Overlapping periods
 * throw InvalidDateRangeException::overlapsExisting($newPeriod, $existingPeriod, 'injury');
 * ```
 */
class InvalidDateRangeException extends BaseBusinessException
{
    /**
     * Exception for end date being before start date.
     */
    public static function endBeforeStart(Carbon $startDate, Carbon $endDate, ?string $context = null): static
    {
        $contextInfo = $context ? " for {$context}" : '';

        /** @var static */
        return new self(
            "Invalid date range{$contextInfo}: end date ({$endDate->format('Y-m-d')}) cannot be before start date ({$startDate->format('Y-m-d')}). Ensure logical date ordering."
        );
    }

    /**
     * Exception for date range violating business rules.
     */
    public static function violatesBusinessRule(Carbon $startDate, Carbon $endDate, string $rule): static
    {
        $duration = $startDate->diffInDays($endDate);

        /** @var static */
        return new self(
            "Date range from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} ({$duration} days) violates business rule: {$rule}"
        );
    }

    /**
     * Exception for date range being too short.
     */
    public static function tooShort(Carbon $startDate, Carbon $endDate, int $minimumDays, string $context): static
    {
        $actualDays = $startDate->diffInDays($endDate);

        /** @var static */
        return new self(
            "{$context} period from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} is too short ({$actualDays} days). Minimum required: {$minimumDays} days."
        );
    }

    /**
     * Exception for date range being too long.
     */
    public static function tooLong(Carbon $startDate, Carbon $endDate, int $maximumDays, string $context): static
    {
        $actualDays = $startDate->diffInDays($endDate);

        /** @var static */
        return new self(
            "{$context} period from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} is too long ({$actualDays} days). Maximum allowed: {$maximumDays} days."
        );
    }

    /**
     * Exception for date range overlapping with existing period.
     */
    /**
     * @param array{start: Carbon, end: Carbon} $newPeriod
     * @param array{start: Carbon, end: Carbon} $existingPeriod
     */
    public static function overlapsExisting(array $newPeriod, array $existingPeriod, string $type): static
    {
        $newStart = $newPeriod['start']->format('Y-m-d');
        $newEnd = $newPeriod['end']->format('Y-m-d');
        $existingStart = $existingPeriod['start']->format('Y-m-d');
        $existingEnd = $existingPeriod['end']->format('Y-m-d');

        /** @var static */
        return new self(
            "New {$type} period ({$newStart} to {$newEnd}) overlaps with existing period ({$existingStart} to {$existingEnd}). Periods cannot overlap."
        );
    }

    /**
     * Exception for date being in the future when past/present required.
     */
    public static function futureNotAllowed(Carbon $date, string $context): static
    {
        /** @var static */
        return new self(
            "{$context} date ({$date->format('Y-m-d')}) cannot be in the future. Use current or past date only."
        );
    }

    /**
     * Exception for date being in the past when future required.
     */
    public static function pastNotAllowed(Carbon $date, string $context): static
    {
        /** @var static */
        return new self(
            "{$context} date ({$date->format('Y-m-d')}) cannot be in the past. Use current or future date only."
        );
    }

    /**
     * Exception for date range not aligning with business calendar.
     */
    public static function notAlignedWithBusinessCalendar(Carbon $startDate, Carbon $endDate, string $requirement): static
    {
        /** @var static */
        return new self(
            "Date range from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} does not align with business calendar requirement: {$requirement}"
        );
    }

    /**
     * Exception for employment period validation.
     */
    public static function invalidEmploymentPeriod(Carbon $startDate, Carbon $endDate, string $reason): static
    {
        /** @var static */
        return new self(
            "Invalid employment period from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}: {$reason}"
        );
    }

    /**
     * Exception for injury period validation.
     */
    public static function invalidInjuryPeriod(Carbon $startDate, Carbon $endDate, string $reason): static
    {
        /** @var static */
        return new self(
            "Invalid injury period from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}: {$reason}"
        );
    }

    /**
     * Exception for suspension period validation.
     */
    public static function invalidSuspensionPeriod(Carbon $startDate, Carbon $endDate, string $reason): static
    {
        /** @var static */
        return new self(
            "Invalid suspension period from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}: {$reason}"
        );
    }

    /**
     * Exception for championship reign validation.
     */
    public static function invalidChampionshipReign(Carbon $startDate, Carbon $endDate, string $reason): static
    {
        /** @var static */
        return new self(
            "Invalid championship reign from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}: {$reason}"
        );
    }

    /**
     * Exception for event date validation.
     */
    public static function invalidEventDate(Carbon $eventDate, string $reason): static
    {
        /** @var static */
        return new self(
            "Invalid event date ({$eventDate->format('Y-m-d')}): {$reason}"
        );
    }

    /**
     * Exception for contract period validation.
     */
    public static function invalidContractPeriod(Carbon $startDate, Carbon $endDate, string $contractType): static
    {
        $duration = $startDate->diffInDays($endDate);

        /** @var static */
        return new self(
            "Invalid {$contractType} contract period from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} ({$duration} days). Check contract terms and business rules."
        );
    }

    /**
     * Exception for age-related date restrictions.
     */
    public static function ageRestrictionViolation(Carbon $birthDate, Carbon $eventDate, int $minimumAge): static
    {
        $ageAtEvent = $birthDate->diffInYears($eventDate);

        /** @var static */
        return new self(
            "Age restriction violation: person born on {$birthDate->format('Y-m-d')} would be {$ageAtEvent} years old on {$eventDate->format('Y-m-d')}, but minimum age is {$minimumAge}."
        );
    }

    /**
     * Exception for seasonal or holiday restrictions.
     */
    public static function seasonalRestriction(Carbon $date, string $restriction): static
    {
        /** @var static */
        return new self(
            "Date {$date->format('Y-m-d')} violates seasonal restriction: {$restriction}"
        );
    }

    /**
     * Exception for date gap validation.
     */
    public static function invalidGap(Carbon $endDate1, Carbon $startDate2, int $requiredGapDays, string $context): static
    {
        $actualGap = $endDate1->diffInDays($startDate2);

        /** @var static */
        return new self(
            "Invalid gap in {$context}: {$actualGap} days between {$endDate1->format('Y-m-d')} and {$startDate2->format('Y-m-d')}. Required gap: {$requiredGapDays} days."
        );
    }

    /**
     * Exception for retroactive date restrictions.
     */
    public static function retroactiveNotAllowed(Carbon $date, string $context, int $maxRetroactiveDays): static
    {
        $daysBack = now()->diffInDays($date);

        /** @var static */
        return new self(
            "Retroactive {$context} date ({$date->format('Y-m-d')}) is {$daysBack} days in the past. Maximum retroactive period: {$maxRetroactiveDays} days."
        );
    }

    /**
     * Exception for weekend/business day restrictions.
     */
    public static function businessDayRequired(Carbon $date, string $context): static
    {
        $dayName = $date->format('l');

        /** @var static */
        return new self(
            "{$context} date ({$date->format('Y-m-d')}) falls on {$dayName}. Business days (Monday-Friday) required."
        );
    }

    /**
     * Exception for fiscal year boundary violations.
     */
    public static function crossesFiscalYear(Carbon $startDate, Carbon $endDate, string $fiscalYearEnd): static
    {
        return new self(
            "Date range from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')} crosses fiscal year boundary ({$fiscalYearEnd}). Periods cannot span fiscal years."
        );
    }
}
