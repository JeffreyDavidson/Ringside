<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use Illuminate\Support\Carbon;

/**
 * Contract for models that have activity periods (activation/deactivation cycles).
 *
 * This contract defines the interface for models that can be activated and deactivated
 * over time, tracking periods of activity with start and end dates.
 */
interface HasActivityPeriods
{
    /**
     * Check if the model is currently activated/active.
     *
     * @return bool True if the model has an active period without an end date
     */
    public function isCurrentlyActive(): bool;

    /**
     * Check if the model is not currently active.
     *
     * @return bool True if the model does not have an active period
     */
    public function isNotCurrentlyActive(): bool;

    /**
     * Check if the model has never been activated.
     *
     * @return bool True if the model has no activity periods
     */
    public function isUnactivated(): bool;

    /**
     * Check if the model is inactive (not currently active).
     *
     * @return bool True if the model is not currently active
     */
    public function isInactive(): bool;

    /**
     * Check if the model was active on a given date.
     *
     * @param  Carbon  $date  The date to check against
     * @return bool True if the model was active on the given date
     */
    public function wasActiveOn(Carbon $date): bool;

    /**
     * Check if the model was active before a given date.
     *
     * @param  Carbon  $date  The date to check against
     * @return bool True if the model was active before the given date
     */
    public function wasActiveBefore(Carbon $date): bool;

    /**
     * Get all activity periods for this model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<*, *>
     */
    public function activityPeriods();

    /**
     * Get the current activity period.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne<*, *>
     */
    public function currentActivityPeriod();
}
