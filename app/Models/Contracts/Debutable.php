<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Concerns\HasActivityPeriods;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Contract for models that can debut and have status changes tracked over time.
 *
 * This interface defines the basic contract for any model that can debut (be introduced)
 * and then have their status changes tracked historically. It provides a standard way to
 * access status change relationships across different model types while maintaining type safety
 * through generics.
 *
 * Perfect for entities like titles and stables that debut at a specific time and then
 * have their status (Active, Inactive, Retired) tracked through status change records.
 *
 * Models implementing this interface should also use the HasActivityPeriods trait
 * to get the complete status change functionality implementation.
 *
 * @template TStatusChange of Model The status change model class
 * @template TModel of Model The model that can debut
 *
 * @see HasActivityPeriods For the trait implementation
 *
 * @example
 * ```php
 * class Title extends Model implements Debutable
 * {
 *     use HasActivityPeriods;
 * }
 *
 * class Stable extends Model implements Debutable
 * {
 *     use HasActivityPeriods;
 * }
 * ```
 */
interface Debutable
{
    /**
     * Get all status changes for the model.
     *
     * This method should return a HasMany relationship that provides access
     * to all status change records associated with the model, ordered chronologically.
     * Each record represents when the entity's status changed.
     *
     * @return HasMany<TStatusChange, TModel>
     *                                        A relationship instance for accessing all status changes
     *
     * @example
     * ```php
     * $model = SomeModel::find(1);
     * $allChanges = $model->statusChanges;
     * $recentChanges = $model->statusChanges()->latest('changed_at')->get();
     * ```
     */
    public function statusChanges(): HasMany;

    /**
     * Get the debut status change record.
     *
     * This method should return a HasOne relationship for the first status change
     * (the debut), which represents when the entity was first introduced.
     *
     * @return HasOne<TStatusChange, TModel>
     *                                       A relationship instance for accessing the debut record
     *
     * @example
     * ```php
     * $model = SomeModel::find(1);
     * $debutRecord = $model->debutStatusChange;
     *
     * if ($model->debutStatusChange()->exists()) {
     *     echo "Entity debuted on: " . $model->debutStatusChange->changed_at;
     * }
     * ```
     */
    public function debutStatusChange(): HasOne;

    /**
     * Get the most recent status change.
     *
     * This method should return a HasOne relationship for the most recent
     * status change, which determines the current status.
     *
     * @return HasOne<TStatusChange, TModel>
     *                                       A relationship instance for accessing the latest status change
     *
     * @example
     * ```php
     * $model = SomeModel::find(1);
     * $latestChange = $model->latestStatusChange;
     * $currentStatus = $model->latestStatusChange->status;
     * ```
     */
    public function latestStatusChange(): HasOne;

    /**
     * Determine if the model has debuted.
     *
     * This method should check if there are any status change records,
     * indicating the entity has been introduced.
     *
     * @return bool True if the model has debuted, false otherwise
     *
     * @example
     * ```php
     * $model = SomeModel::find(1);
     *
     * if ($model->hasDebuted()) {
     *     echo "Entity has been introduced";
     * }
     * ```
     */
    public function hasDebuted(): bool;
}
