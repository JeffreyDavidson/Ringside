<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Concerns\IsRetirable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Contract for models that can be retired.
 *
 * This interface defines the basic contract for any model that can have retirements.
 * It provides a standard way to access retirement relationships across different
 * model types while maintaining type safety through generics.
 *
 * Models implementing this interface should also use the IsRetirable trait
 * to get the complete retirement functionality implementation.
 *
 * @template-covariant TRetirement of Model The retirement model class
 * @template-covariant TModel of Model The model that can be retired
 *
 * @see IsRetirable For the trait implementation
 *
 * @example
 * ```php
 * class Wrestler extends Model implements Retirable
 * {
 *     use IsRetirable;
 * }
 *
 * class Manager extends Model implements Retirable
 * {
 *     use IsRetirable;
 * }
 * ```
 */
interface Retirable
{
    /**
     * Get all retirements for the model.
     *
     * This method should return a HasMany relationship that provides access
     * to all retirement records associated with the model, regardless of status.
     *
     * @return HasMany<TRetirement, TModel>
     *                                      A relationship instance for accessing all retirements
     *
     * @example
     * ```php
     * $model = SomeModel::find(1);
     * $allRetirements = $model->retirements;
     * $activeRetirements = $model->retirements()->whereNull('ended_at')->get();
     * ```
     */
    public function retirements(): HasMany;

    /**
     * Get the current active retirement for the model.
     *
     * This method should return a HasOne relationship that provides access
     * to the currently active retirement (where ended_at is null). Returns
     * null if the model is not currently retired.
     *
     * @return HasOne<TRetirement, TModel>
     *                                     A relationship instance for accessing the current retirement
     *
     * @example
     * ```php
     * $model = SomeModel::find(1);
     * $currentRetirement = $model->currentRetirement;
     *
     * if ($currentRetirement) {
     *     echo "Model is retired since: " . $currentRetirement->started_at;
     * }
     * ```
     */
    public function currentRetirement(): HasOne;

    /**
     * Check if the model is currently retired.
     *
     * This method should return true if there is an active retirement record
     * (where ended_at is null) for the model.
     *
     * @return bool True if currently retired, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * if ($wrestler->isRetired()) {
     *     echo "Wrestler is currently retired";
     * }
     * ```
     */
    public function isRetired(): bool;
}
