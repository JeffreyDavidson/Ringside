<?php

declare(strict_types=1);

namespace App\Database\Query\Concerns;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait for adding activity period-related query scopes.
 *
 * Provides common query scopes for models that have activity period
 * relationships like titles and stables. These scopes help filter
 * models based on their current activity status and historical periods.
 *
 * This trait is designed to work with models that implement the
 * HasActivityPeriods trait and have relationships like:
 * - currentActivityPeriod (for currently active records)
 * - activityPeriods (for all activity period history)
 * - previousActivityPeriods (for completed periods)
 *
 * @author Your Name
 *
 * @since 1.0.0
 * @see HasActivityPeriods For the activity period trait
 *
 * @example
 * ```php
 * // In Title or Stable model:
 * class Title extends Model
 * {
 *     use HasActivityPeriods;
 *     use HasStatusScopes;
 * }
 *
 * // Usage:
 * $activeTitles = Title::currentlyActive()->get();
 * $inactiveTitles = Title::currentlyInactive()->get();
 * $recentlyActivated = Title::activatedAfter($date)->get();
 * ```
 */
trait HasStatusScopes
{
    /**
     * Scope a query to only include models that are currently active.
     *
     * Filters to models that have a current activity period (where ended_at is null).
     * This indicates the model is presently active and operational.
     *
     * @param  Builder<static>  $query  The query builder instance
     * @return Builder<static> The modified query builder
     *
     * @example
     * ```php
     * // Get all currently active titles
     * $activeTitles = Title::currentlyActive()->get();
     *
     * // Get currently active stables
     * $activeStables = Stable::currentlyActive()->get();
     * ```
     */
    public function scopeCurrentlyActive(Builder $query): Builder
    {
        return $query->whereHas('currentActivityPeriod');
    }

    /**
     * Scope a query to only include models that are currently inactive.
     *
     * Filters to models that do not have a current activity period.
     * This indicates the model is presently inactive or unactivated.
     *
     * @param  Builder<static>  $query  The query builder instance
     * @return Builder<static> The modified query builder
     *
     * @example
     * ```php
     * // Get all inactive titles
     * $inactiveTitles = Title::currentlyInactive()->get();
     *
     * // Get inactive stables
     * $inactiveStables = Stable::currentlyInactive()->get();
     * ```
     */
    public function scopeCurrentlyInactive(Builder $query): Builder
    {
        return $query->whereDoesntHave('currentActivityPeriod');
    }

    /**
     * Scope a query to models with activity periods within a date range.
     *
     * Finds models that had activity periods that started within the specified
     * date range, regardless of whether they are still active or have ended.
     *
     * @param  Builder<static>  $query  The query builder instance
     * @param  Carbon  $start  Start date for the range
     * @param  Carbon  $end  End date for the range
     * @return Builder<static> The modified query builder
     *
     * @example
     * ```php
     * // Get titles that were active during Q1 2024
     * $q1Titles = Title::activeDuring(
     *     Carbon::parse('2024-01-01'),
     *     Carbon::parse('2024-03-31')
     * )->get();
     *
     * // Get stables active in the last year
     * $recentStables = Stable::activeDuring(
     *     Carbon::now()->subYear(),
     *     Carbon::now()
     * )->get();
     * ```
     */
    public function scopeActiveDuring(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereHas('activityPeriods', function ($q) use ($start, $end) {
            $q->where('started_at', '>=', $start)
                ->where(function ($q2) use ($end) {
                    $q2->whereNull('ended_at')
                        ->orWhere('started_at', '<=', $end);
                });
        });
    }

    /**
     * Scope a query to models that were activated after a certain date.
     *
     * Finds models that started their activity periods after the specified date.
     * Useful for finding recently activated titles or newly formed stables.
     *
     * @param  Builder<static>  $query  The query builder instance
     * @param  Carbon  $date  The date to compare against
     * @return Builder<static> The modified query builder
     *
     * @example
     * ```php
     * // Get titles activated in the last 30 days
     * $recentTitles = Title::activatedAfter(Carbon::now()->subDays(30))->get();
     *
     * // Get stables formed this year
     * $newStables = Stable::activatedAfter(Carbon::now()->startOfYear())->get();
     * ```
     */
    public function scopeActivatedAfter(Builder $query, Carbon $date): Builder
    {
        return $query->whereHas('activityPeriods', function ($q) use ($date) {
            $q->where('started_at', '>', $date);
        });
    }

    /**
     * Scope a query to models that were activated before a certain date.
     *
     * Finds models that started their activity periods before the specified date.
     * Useful for finding established titles or long-running stables.
     *
     * @param  Builder<static>  $query  The query builder instance
     * @param  Carbon  $date  The date to compare against
     * @return Builder<static> The modified query builder
     *
     * @example
     * ```php
     * // Get titles that existed before 2020
     * $legacyTitles = Title::activatedBefore(Carbon::parse('2020-01-01'))->get();
     *
     * // Get stables that formed before last year
     * $establishedStables = Stable::activatedBefore(Carbon::now()->subYear())->get();
     * ```
     */
    public function scopeActivatedBefore(Builder $query, Carbon $date): Builder
    {
        return $query->whereHas('activityPeriods', function ($q) use ($date) {
            $q->where('started_at', '<', $date);
        });
    }

    /**
     * Scope a query to models that were deactivated after a certain date.
     *
     * Finds models that ended their activity periods after the specified date.
     * Useful for finding recently retired titles or dissolved stables.
     *
     * @param  Builder<static>  $query  The query builder instance
     * @param  Carbon  $date  The date to compare against
     * @return Builder<static> The modified query builder
     *
     * @example
     * ```php
     * // Get titles that were retired in the last 6 months
     * $recentlyRetired = Title::deactivatedAfter(Carbon::now()->subMonths(6))->get();
     *
     * // Get stables that dissolved this year
     * $dissolvedStables = Stable::deactivatedAfter(Carbon::now()->startOfYear())->get();
     * ```
     */
    public function scopeDeactivatedAfter(Builder $query, Carbon $date): Builder
    {
        return $query->whereHas('previousActivityPeriods', function ($q) use ($date) {
            $q->where('ended_at', '>', $date);
        });
    }

    /**
     * Scope a query to models that have never been activated.
     *
     * Finds models that have no activity periods at all. These are typically
     * newly created titles or stables that haven't been put into active use yet.
     *
     * @param  Builder<static>  $query  The query builder instance
     * @return Builder<static> The modified query builder
     *
     * @example
     * ```php
     * // Get titles that have never been activated
     * $unactivatedTitles = Title::neverActivated()->get();
     *
     * // Get stables that have never been active
     * $dormantStables = Stable::neverActivated()->get();
     * ```
     */
    public function scopeNeverActivated(Builder $query): Builder
    {
        return $query->whereDoesntHave('activityPeriods');
    }

    /**
     * Scope a query to models that have had multiple activity periods.
     *
     * Finds models that have been reactivated at least once, indicating
     * they have had periods of activity, inactivity, and then reactivation.
     *
     * @param  Builder<static>  $query  The query builder instance
     * @param  int  $minimumPeriods  Minimum number of periods required (default: 2)
     * @return Builder<static> The modified query builder
     *
     * @example
     * ```php
     * // Get titles that have been reactivated at least once
     * $reactivatedTitles = Title::withMultiplePeriods()->get();
     *
     * // Get stables that have reformed multiple times
     * $reformedStables = Stable::withMultiplePeriods(3)->get(); // At least 3 periods
     * ```
     */
    public function scopeWithMultiplePeriods(Builder $query, int $minimumPeriods = 2): Builder
    {
        return $query->has('activityPeriods', '>=', $minimumPeriods);
    }
}
