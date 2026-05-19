<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Enums\Shared\EmploymentStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Contract for models that track employment history and status.
 *
 * This interface defines the standard contract for any model that can have
 * employment relationships over time, including checking employment status,
 * future employment commitments, and historical employment records.
 *
 * Models implementing this interface should provide comprehensive employment
 * tracking with proper time-based filtering and status management.
 *
 * @template TEmployment of \Illuminate\Database\Eloquent\Model The employment model class
 * @template TModel of \Illuminate\Database\Eloquent\Model The model that can be employed
 *
 * @example
 * ```php
 * class Wrestler extends Model implements HasEmploymentHistory
 * {
 *     use TracksEmploymentHistory;
 *
 *     // Usage:
 *     // $wrestler->hasStatus(EmploymentStatus::Employed) - Check specific status
 *     // $wrestler->hasFutureEmployment() - Check for future employment
 *     // $wrestler->employmentHistory - Get all employment records
 * }
 * ```
 */
interface HasEmploymentHistory
{
    /**
     * Get all employment records for this entity.
     *
     * This method should return a HasMany relationship that provides access
     * to all employment records associated with the entity, regardless of status.
     *
     * @return HasMany<TEmployment, TModel>
     *                                      A relationship instance for accessing all employment records
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $allEmployments = $wrestler->employmentHistory;
     * $employmentCount = $wrestler->employmentHistory()->count();
     * ```
     */
    public function employmentHistory(): HasMany;

    /**
     * Get the current active employment record.
     *
     * This method should return a HasOne relationship that provides access
     * to the currently active employment record (where ended_at is null).
     * Returns null if the entity is not currently employed.
     *
     * @return HasOne<TEmployment, TModel>
     *                                     A relationship instance for the current employment
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $currentEmployment = $wrestler->currentEmployment;
     *
     * if ($currentEmployment) {
     *     echo "Employed since: " . $currentEmployment->started_at;
     * }
     * ```
     */
    public function currentEmployment(): HasOne;

    /**
     * Check if the entity has a specific employment status.
     *
     * This method should check if the entity currently has the specified
     * employment status, considering both current and historical records.
     *
     * @param  EmploymentStatus  $status  The employment status to check for
     * @return bool True if the entity has the specified status, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->hasStatus(EmploymentStatus::Employed)) {
     *     echo "Wrestler is currently employed";
     * }
     *
     * if ($wrestler->hasStatus(EmploymentStatus::Released)) {
     *     echo "Wrestler was previously released";
     * }
     * ```
     */
    public function hasStatus(EmploymentStatus $status): bool;

    /**
     * Check if the entity has future employment commitments.
     *
     * This method should check if there are any employment records with
     * start dates in the future, indicating scheduled employment.
     *
     * @return bool True if there are future employment commitments, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->hasFutureEmployment()) {
     *     echo "Wrestler has upcoming employment scheduled";
     * }
     * ```
     */
    public function hasFutureEmployment(): bool;

    /**
     * Get the most recent employment status.
     *
     * This method should return the employment status from the most recent
     * employment record, whether current or historical.
     *
     * @return EmploymentStatus|null The most recent employment status, or null if no history
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $status = $wrestler->getMostRecentEmploymentStatus();
     *
     * if ($status === EmploymentStatus::Released) {
     *     echo "Wrestler was most recently released";
     * }
     * ```
     */
    public function getMostRecentEmploymentStatus(): ?EmploymentStatus;

    /**
     * Get employment records within a date range.
     *
     * This method should return employment records that were active during
     * the specified date range, considering both start and end dates.
     *
     * @param  Carbon  $startDate  The range start date
     * @param  Carbon  $endDate  The range end date
     * @return Collection<int, TEmployment>
     *                                      Employment records active during the specified range
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $lastYear = $wrestler->getEmploymentInRange(
     *     now()->subYear(),
     *     now()
     * );
     *
     * echo "Employment periods in the last year: " . $lastYear->count();
     * ```
     */
    public function getEmploymentInRange(Carbon $startDate, Carbon $endDate): Collection;

    /**
     * Check if the entity was employed on a specific date.
     *
     * This method should check if there was an active employment record
     * on the specified date.
     *
     * @param  Carbon  $date  The date to check employment for
     * @return bool True if the entity was employed on the date, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $wasEmployed = $wrestler->wasEmployedOn(Carbon::parse('2024-01-01'));
     *
     * if ($wasEmployed) {
     *     echo "Wrestler was employed on January 1st, 2024";
     * }
     * ```
     */
    public function wasEmployedOn(Carbon $date): bool;

    /**
     * Get the total duration of employment across all periods.
     *
     * This method should calculate the total time the entity has been
     * employed, summing all employment periods.
     *
     * @return int Total employment duration in days
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $totalDays = $wrestler->getTotalEmploymentDuration();
     *
     * echo "Total employment duration: " . $totalDays . " days";
     * ```
     */
    public function getTotalEmploymentDuration(): int;
}
