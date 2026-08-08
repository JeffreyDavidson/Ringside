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
     * @throws InvalidArgumentException When the date range is invalid
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
