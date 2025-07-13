<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Concerns\IsEmployable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Contract for models that can be employed.
 *
 * This interface defines the basic contract for any model that can have employment.
 * It provides a standard way to access employment relationships across different
 * model types while maintaining type safety through generics.
 *
 * Models implementing this interface should also use the IsEmployable trait
 * to get the complete employment functionality implementation.
 *
 * @template-covariant TEmployment of Model The employment model class
 * @template-covariant TModel of Model The model that can be employed
 *
 * @see IsEmployable For the trait implementation
 *
 * @example
 * ```php
 * class Wrestler extends Model implements Employable
 * {
 *     use IsEmployable;
 * }
 *
 * class Manager extends Model implements Employable
 * {
 *     use IsEmployable;
 * }
 * ```
 */
interface Employable
{
    /**
     * Get all employments for the model.
     *
     * This method should return a HasMany relationship that provides access
     * to all employment records associated with the model, regardless of status.
     *
     * @return HasMany<TEmployment, TModel>
     *                                      A relationship instance for accessing all employments
     *
     * @example
     * ```php
     * $model = SomeModel::find(1);
     * $allEmployments = $model->employments;
     * $activeEmployments = $model->employments()->whereNull('ended_at')->get();
     * ```
     */
    public function employments(): HasMany;

    /**
     * Get the current active employment for the model.
     *
     * This method should return a HasOne relationship that provides access
     * to the currently active employment (where ended_at is null). Returns
     * null if the model is not currently employed.
     *
     * @return HasOne<TEmployment, TModel>
     *                                     A relationship instance for accessing the current employment
     *
     * @example
     * ```php
     * $model = SomeModel::find(1);
     * $currentEmployment = $model->currentEmployment;
     *
     * if ($currentEmployment) {
     *     echo "Model is employed since: " . $currentEmployment->started_at;
     * }
     * ```
     */
    public function currentEmployment(): HasOne;

    /**
     * Check if the model is currently employed.
     *
     * This method should return true if there is an active employment record
     * (where ended_at is null) for the model.
     *
     * @return bool True if currently employed, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * if ($wrestler->isEmployed()) {
     *     echo "Wrestler is currently employed";
     * }
     * ```
     */
    public function isEmployed(): bool;

    /**
     * Check if the model is currently released.
     *
     * This method should return true if the model has been released
     * from employment (has released status).
     *
     * @return bool True if currently released, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * if ($wrestler->isReleased()) {
     *     echo "Wrestler is currently released";
     * }
     * ```
     */
    public function isReleased(): bool;
}
