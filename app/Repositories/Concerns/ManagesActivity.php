<?php

declare(strict_types=1);

namespace App\Repositories\Concerns;

use App\Models\Contracts\HasActivityPeriods;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

/**
 * Trait for repositories that manage activity periods.
 *
 * This trait provides standardized methods for creating and ending activity periods
 * across different repository classes. It works with any model that implements
 * the HasActivityPeriods contract, ensuring consistent activity management behavior.
 *
 * Activity periods represent time ranges when an entity is active (e.g., title
 * activations, stable activations). Each period has a start date and optionally
 * an end date for tracking historical activity.
 *
 * @template TActivityPeriod of Model
 * @template TModel of HasActivityPeriods&\Illuminate\Database\Eloquent\Model
 *
 * @see HasActivityPeriods For the required model contract
 * @see HasActivityPeriods For the model trait implementation
 *
 * @example
 * ```php
 * class TitleRepository extends BaseRepository
 * {
 *     use ManagesActivity;
 *
 *     public function activate(Title $title, Carbon $startDate): void
 *     {
 *         $this->createActivity($title, $startDate);
 *     }
 *
 *     public function deactivate(Title $title, Carbon $endDate): void
 *     {
 *         $this->endActivity($title, $endDate);
 *     }
 * }
 * ```
 */
trait ManagesActivity
{
    /**
     * Create an activity period for the given model.
     *
     * Creates a new activity period for the model starting at the specified date.
     * Uses updateOrCreate to ensure only one active period exists at a time,
     * automatically ending any previous activity period.
     *
     * @param  HasActivityPeriods  $model  The model to activate
     * @param  Carbon  $startDate  The date when the activity period begins
     *
     * @throws QueryException If the activity period creation fails
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * $this->createActivity($title, Carbon::parse('2024-01-15'));
     * ```
     */
    public function createActivity(HasActivityPeriods $model, Carbon $startDate): void
    {
        $model->activityPeriods()->updateOrCreate(
            ['ended_at' => null],
            ['started_at' => $startDate->toDateTimeString()]
        );
    }

    /**
     * End the current active activity period for the given model.
     *
     * Finds the currently active activity period (where ended_at is null) and sets
     * the ended_at timestamp to the specified date. If no active activity period
     * exists, this method will do nothing.
     *
     * @param  HasActivityPeriods  $model  The model whose activity period should be ended
     * @param  Carbon  $endDate  The date when the activity period ends
     *
     * @throws QueryException If the activity period update fails
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * $this->endActivity($title, Carbon::parse('2024-02-15'));
     * ```
     */
    public function endActivity(HasActivityPeriods $model, Carbon $endDate): void
    {
        $model->currentActivityPeriod()->update(['ended_at' => $endDate->toDateTimeString()]);
    }
}
