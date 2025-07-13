<?php

declare(strict_types=1);

namespace App\Actions\Concerns;

use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * Provides date management utilities for action classes.
 *
 * This trait offers common date handling functionality used across various
 * wrestling promotion action classes for consistent date processing.
 *
 * COMMON USE CASES:
 * - Employment start/end dates with defaults
 * - Injury occurrence dates
 * - Retirement and suspension dates
 * - Activation and debut dates
 * - Event scheduling dates
 *
 * @example
 * ```php
 * class SomeAction extends BaseAction
 * {
 *     use ManagesDates;
 *
 *     public function handle(Model $model, ?Carbon $date = null): void
 *     {
 *         $effectiveDate = $this->getEffectiveDate($date);
 *         // Use $effectiveDate for operation
 *     }
 * }
 * ```
 */
trait ManagesDates
{
    /**
     * Get the effective date for an operation, defaulting to now if not provided.
     *
     * This method provides consistent date handling across all action classes,
     * ensuring that operations have a valid date whether explicitly provided
     * or defaulted to the current timestamp.
     *
     * @param  Carbon|null  $date  The provided date, or null to use current timestamp
     * @return Carbon The effective date to use for the operation
     *
     * @example
     * ```php
     * // Using provided date
     * $effectiveDate = $this->getEffectiveDate(Carbon::parse('2024-01-01'));
     *
     * // Using current timestamp as default
     * $effectiveDate = $this->getEffectiveDate(null);
     *
     * // Typical usage in action methods
     * public function handle(Wrestler $wrestler, ?Carbon $employmentDate = null): void
     * {
     *     $employmentDate = $this->getEffectiveDate($employmentDate);
     *     // Proceed with employment logic using $employmentDate
     * }
     * ```
     */
    protected function getEffectiveDate(?Carbon $date = null): Carbon
    {
        return $date ?? now();
    }

    /**
     * Get the effective start date for a period, ensuring it's not in the future.
     *
     * This method is useful for operations that should not be backdated beyond
     * the current timestamp, such as immediate status changes.
     *
     * @param  Carbon|null  $date  The provided start date
     * @return Carbon The effective start date (current time if date is in future)
     *
     * @example
     * ```php
     * // Prevents future-dating immediate actions
     * $startDate = $this->getEffectiveStartDate(Carbon::tomorrow()); // Returns now()
     * $startDate = $this->getEffectiveStartDate(Carbon::yesterday()); // Returns yesterday
     * ```
     */
    protected function getEffectiveStartDate(?Carbon $date = null): Carbon
    {
        $effectiveDate = $this->getEffectiveDate($date);

        return $effectiveDate->isFuture() ? now() : $effectiveDate;
    }

    /**
     * Get the effective end date for a period, defaulting to now if not provided.
     *
     * This method is specifically for ending operations like employment termination,
     * injury recovery, or suspension lifting.
     *
     * @param  Carbon|null  $date  The provided end date, or null to end immediately
     * @return Carbon The effective end date to use for the operation
     *
     * @example
     * ```php
     * // End employment immediately
     * $endDate = $this->getEffectiveEndDate(null);
     *
     * // End employment on specific date
     * $endDate = $this->getEffectiveEndDate(Carbon::parse('2024-12-31'));
     * ```
     */
    protected function getEffectiveEndDate(?Carbon $date = null): Carbon
    {
        return $this->getEffectiveDate($date);
    }

    /**
     * Validate that a date range is logical (start <= end).
     *
     * @param  Carbon  $startDate  The start date of the period
     * @param  Carbon  $endDate  The end date of the period
     * @return bool True if the date range is valid
     *
     * @example
     * ```php
     * $start = Carbon::parse('2024-01-01');
     * $end = Carbon::parse('2024-12-31');
     *
     * if (!$this->isValidDateRange($start, $end)) {
     *     throw new InvalidArgumentException('End date must be after start date');
     * }
     * ```
     */
    protected function isValidDateRange(Carbon $startDate, Carbon $endDate): bool
    {
        return $startDate->lte($endDate);
    }

    /**
     * Ensure a date range is valid, throwing an exception if not.
     *
     * @param  Carbon  $startDate  The start date of the period
     * @param  Carbon  $endDate  The end date of the period
     *
     * @throws InvalidArgumentException When the date range is invalid
     *
     * @example
     * ```php
     * $this->ensureValidDateRange($employmentStart, $employmentEnd);
     * // Proceeds only if start <= end, otherwise throws exception
     * ```
     */
    protected function ensureValidDateRange(Carbon $startDate, Carbon $endDate): void
    {
        if (! $this->isValidDateRange($startDate, $endDate)) {
            throw new InvalidArgumentException(
                "End date ({$endDate->toDateString()}) must be after or equal to start date ({$startDate->toDateString()})"
            );
        }
    }
}
