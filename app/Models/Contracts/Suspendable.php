<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Concerns\IsSuspendable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Contract for models that can be suspended.
 *
 * This interface defines the basic contract for any model that can have suspensions.
 * It provides a standard way to access suspension relationships across different
 * model types while maintaining type safety through generics.
 *
 * Models implementing this interface should also use the IsSuspendable trait
 * to get the complete suspension functionality implementation.
 *
 * @template-covariant TSuspension of Model The suspension model class
 * @template-covariant TModel of Model The model that can be suspended
 *
 * @see IsSuspendable For the trait implementation
 *
 * @example
 * ```php
 * class Wrestler extends Model implements Suspendable
 * {
 *     use IsSuspendable;
 * }
 *
 * class Manager extends Model implements Suspendable
 * {
 *     use IsSuspendable;
 * }
 * ```
 */
interface Suspendable
{
    /**
     * Get all suspensions for the model.
     *
     * This method should return a HasMany relationship that provides access
     * to all suspension records associated with the model, regardless of status.
     *
     * @return HasMany<TSuspension, TModel>
     *                                      A relationship instance for accessing all suspensions
     *
     * @example
     * ```php
     * $model = SomeModel::find(1);
     * $allSuspensions = $model->suspensions;
     * $activeSuspensions = $model->suspensions()->whereNull('ended_at')->get();
     * ```
     */
    public function suspensions(): HasMany;

    /**
     * Get the current active suspension for the model.
     *
     * This method should return a HasOne relationship that provides access
     * to the currently active suspension (where ended_at is null). Returns
     * null if the model is not currently suspended.
     *
     * @return HasOne<TSuspension, TModel>
     *                                     A relationship instance for accessing the current suspension
     *
     * @example
     * ```php
     * $model = SomeModel::find(1);
     * $currentSuspension = $model->currentSuspension;
     *
     * if ($currentSuspension) {
     *     echo "Model is suspended since: " . $currentSuspension->started_at;
     * }
     * ```
     */
    public function currentSuspension(): HasOne;

    /**
     * Check if the model is currently suspended.
     *
     * This method should return true if there is an active suspension record
     * (where ended_at is null) for the model.
     *
     * @return bool True if currently suspended, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * if ($wrestler->isSuspended()) {
     *     echo "Wrestler is currently suspended";
     * }
     * ```
     */
    public function isSuspended(): bool;
}
