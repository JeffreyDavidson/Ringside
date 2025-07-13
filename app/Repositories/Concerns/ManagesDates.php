<?php

declare(strict_types=1);

namespace App\Repositories\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Trait for managing date-related operations in repositories.
 *
 * Provides standardized methods for handling start/end dates, active periods,
 * and temporal queries across different repository types. This trait abstracts
 * common patterns for time-based entity management such as employment periods,
 * activity periods, and other temporal relationships.
 *
 * The trait works with any model that has time-based relationships, providing
 * a consistent interface for starting and ending periods, checking active states,
 * and calculating durations.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 * @template TPeriod of \Illuminate\Database\Eloquent\Model
 *
 * @example
 * ```php
 * class WrestlerRepository extends BaseRepository
 * {
 *     use ManagesDates;
 *
 *     public function employ(Wrestler $wrestler, Carbon $startDate): Model
 *     {
 *         return $this->startPeriod($wrestler, 'employments', $startDate);
 *     }
 *
 *     public function release(Wrestler $wrestler, Carbon $endDate): bool
 *     {
 *         return $this->endCurrentPeriod($wrestler, 'currentEmployment', $endDate);
 *     }
 * }
 * ```
 */
trait ManagesDates
{
    /**
     * Start a period for a model (employment, activation, etc.).
     *
     * @param  Model  $model  The model to start the period for
     * @param  string  $relationship  The relationship name (e.g., 'employments')
     * @param  Carbon  $startDate  When the period starts
     * @param  array<string, mixed>  $additionalData  Additional data for the period record
     * @return Model The created period record
     */
    protected function startPeriod(
        Model $model,
        string $relationship,
        Carbon $startDate,
        array $additionalData = []
    ): Model {
        return $model->{$relationship}()->create(array_merge([
            'started_at' => $startDate,
        ], $additionalData));
    }

    /**
     * End the current active period for a model.
     *
     * @param  Model  $model  The model to end the period for
     * @param  string  $currentRelationship  The current period relationship name
     * @param  Carbon  $endDate  When the period ends
     * @return bool True if a period was ended
     */
    protected function endCurrentPeriod(Model $model, string $currentRelationship, Carbon $endDate): bool
    {
        $currentPeriod = $model->{$currentRelationship}()->first();

        if ($currentPeriod) {
            $currentPeriod->update(['ended_at' => $endDate]);

            return true;
        }

        return false;
    }

    /**
     * Check if a model has an active period.
     *
     * @param  Model  $model  The model to check
     * @param  string  $currentRelationship  The current period relationship name
     * @return bool True if there's an active period
     */
    protected function hasActivePeriod(Model $model, string $currentRelationship): bool
    {
        return $model->{$currentRelationship}()->exists();
    }

    /**
     * Get the duration of the current active period.
     *
     * @param  Model  $model  The model to check
     * @param  string  $currentRelationship  The current period relationship name
     * @return int|null Duration in days, or null if no active period
     */
    protected function getCurrentPeriodDuration(Model $model, string $currentRelationship): ?int
    {
        $currentPeriod = $model->{$currentRelationship}()->first();

        if ($currentPeriod && $currentPeriod->started_at) {
            return (int) $currentPeriod->started_at->diffInDays(now());
        }

        return null;
    }
}
