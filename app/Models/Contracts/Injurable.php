<?php

declare(strict_types=1);

namespace App\Models\Contracts;

use App\Models\Concerns\IsInjurable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Contract for models that can be injured.
 *
 * This interface defines the basic contract for any model that can have injuries.
 * It provides a standard way to access injury relationships across different
 * model types while maintaining type safety through generics.
 *
 * Models implementing this interface should also use the IsInjurable trait
 * to get the complete injury functionality implementation.
 *
 * @template-covariant TInjury of Model The injury model class
 * @template-covariant TModel of Model The model that can be injured
 *
 * @see IsInjurable For the trait implementation
 *
 * @example
 * ```php
 * class Wrestler extends Model implements Injurable
 * {
 *     use IsInjurable;
 * }
 *
 * class Manager extends Model implements Injurable
 * {
 *     use IsInjurable;
 * }
 * ```
 */
interface Injurable
{
    /**
     * Get all injuries for the model.
     *
     * This method should return a HasMany relationship that provides access
     * to all injury records associated with the model, regardless of status.
     *
     * @return HasMany<TInjury, TModel>
     *                                  A relationship instance for accessing all injuries
     *
     * @example
     * ```php
     * $model = SomeModel::find(1);
     * $allInjuries = $model->injuries;
     * $activeInjuries = $model->injuries()->whereNull('ended_at')->get();
     * ```
     */
    public function injuries(): HasMany;

    /**
     * Get the current active injury for the model.
     *
     * This method should return a HasOne relationship that provides access
     * to the currently active injury (where ended_at is null). Returns
     * null if the model is not currently injured.
     *
     * @return HasOne<TInjury, TModel>
     *                                 A relationship instance for accessing the current injury
     *
     * @example
     * ```php
     * $model = SomeModel::find(1);
     * $currentInjury = $model->currentInjury;
     *
     * if ($currentInjury) {
     *     echo "Model is injured since: " . $currentInjury->started_at;
     * }
     * ```
     */
    public function currentInjury(): HasOne;

    /**
     * Check if the model is currently injured.
     *
     * This method should return true if there is an active injury record
     * (where ended_at is null) for the model.
     *
     * @return bool True if currently injured, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * if ($wrestler->isInjured()) {
     *     echo "Wrestler is currently injured";
     * }
     * ```
     */
    public function isInjured(): bool;
}
