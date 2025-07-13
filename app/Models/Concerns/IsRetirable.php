<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use RuntimeException;

/**
 * Adds retirement-related behavior to a model.
 *
 * This trait provides a complete retirement system for Eloquent models, including
 * methods to manage current retirements, historical retirements, and retirement status.
 * It automatically resolves the related retirement model class based on naming conventions.
 *
 * @template TRetirement of Model The retirement model class (e.g., WrestlerRetirement)
 * @template TModel of Model The parent model class that can be retired (e.g., Wrestler)
 *
 * @phpstan-require-implements Retirable<TRetirement, TModel>
 *
 * @see Retirable
 *
 * @example
 * ```php
 * // In your model:
 * class Wrestler extends Model implements Retirable
 * {
 *     use IsRetirable;
 * }
 *
 * // Usage:
 * $wrestler = Wrestler::find(1);
 * $wrestler->isRetired();              // Check if currently retired
 * $wrestler->currentRetirement();      // Get active retirement
 * $wrestler->previousRetirements();    // Get completed retirements
 * ```
 */
trait IsRetirable
{
    use ResolvesRelatedModels;

    /**
     * Get all retirements for the model.
     *
     * This method returns a HasMany relationship that includes all retirement records
     * for the model, regardless of their status (active, completed, etc.).
     *
     * @return HasMany<TRetirement, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $allRetirements = $wrestler->retirements;
     * $retirementCount = $wrestler->retirements()->count();
     * ```
     */
    public function retirements(): HasMany
    {
        /** @var HasMany<TRetirement, TModel> $relation */
        $relation = $this->hasMany($this->resolveRetirementModelClass());

        return $relation;
    }

    /**
     * Get the current (active) retirement.
     *
     * Returns a HasOne relationship for the currently active retirement.
     * An active retirement is one where the 'ended_at' field is null.
     *
     * @return HasOne<TRetirement, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $currentRetirement = $wrestler->currentRetirement;
     *
     * if ($wrestler->currentRetirement()->exists()) {
     *     echo "Wrestler is currently retired";
     * }
     * ```
     */
    public function currentRetirement(): HasOne
    {
        /** @var HasOne<TRetirement, TModel> $relation */
        $relation = $this->hasOne($this->resolveRetirementModelClass())
            ->whereNull('ended_at');

        return $relation;
    }

    /**
     * Get all completed retirements.
     *
     * Returns a HasMany relationship for retirements that have ended.
     * A completed retirement is one where the 'ended_at' field is not null.
     *
     * @return HasMany<TRetirement, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $completedRetirements = $wrestler->previousRetirements;
     * $retirementHistory = $wrestler->previousRetirements()->orderBy('ended_at', 'desc')->get();
     * ```
     */
    public function previousRetirements(): HasMany
    {
        /** @var HasMany<TRetirement, TModel> $relation */
        $relation = $this->hasMany($this->resolveRetirementModelClass())
            ->whereNotNull('ended_at');

        return $relation;
    }

    /**
     * Get the most recent completed retirement.
     *
     * Returns a HasOne relationship for the most recently completed retirement,
     * determined by the highest 'ended_at' value.
     *
     * @return HasOne<TRetirement, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $lastRetirement = $wrestler->previousRetirement;
     *
     * if ($wrestler->previousRetirement()->exists()) {
     *     $endDate = $wrestler->previousRetirement->ended_at;
     * }
     * ```
     */
    public function previousRetirement(): HasOne
    {
        /** @var HasOne<TRetirement, TModel> $relation */
        $relation = $this->hasOne($this->resolveRetirementModelClass())
            ->whereNotNull('ended_at')
            ->ofMany('ended_at', 'max');

        return $relation;
    }

    /**
     * Determine if the model is currently retired.
     *
     * Checks if there is an active retirement (one with a null 'ended_at' field).
     * This is a convenience method that's more efficient than loading the full
     * relationship just to check existence.
     *
     * @return bool True if the model is currently retired, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->isRetired()) {
     *     echo "Cannot book this wrestler - they are retired";
     * }
     * ```
     */
    public function isRetired(): bool
    {
        return $this->currentRetirement()->exists();
    }

    /**
     * Determine if the model has any retirements at all.
     *
     * Checks if there are any retirement records associated with this model,
     * regardless of their status (active or completed). This is useful for
     * determining if a model has a retirement history.
     *
     * @return bool True if the model has any retirements, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->hasRetirements()) {
     *     echo "This wrestler has a retirement history";
     * }
     * ```
     */
    public function hasRetirements(): bool
    {
        return $this->retirements()->exists();
    }

    /**
     * Resolve the model class for the retirement relation.
     *
     * This method automatically determines the retirement model class based on naming
     * conventions. For example, if the parent model is 'Wrestler', it will look for
     * a 'WrestlerRetirement' model class.
     *
     * The resolution can be overridden by calling the fakeRetirementModel() method (useful for testing).
     *
     * @return class-string<TRetirement> The fully qualified class name of the retirement model
     *
     * @throws RuntimeException If the resolved model class doesn't exist
     *
     * @see fakeRetirementModel() For overriding the resolved model class
     *
     * @example
     * For a 'Wrestler' model, this will resolve to 'App\\Models\\Wrestlers\\WrestlerRetirement'
     */
    protected function resolveRetirementModelClass(): string
    {
        return $this->resolveRelatedModelClass('Retirement');
    }

    /**
     * Override the resolved model class for testing or customization.
     *
     * This method allows you to override the automatic model class resolution,
     * which is particularly useful for testing scenarios where you might want
     * to use a different model class or mock.
     *
     * @param  class-string<TRetirement>  $class  The fully qualified class name to use
     *
     * @example
     * ```php
     * // In a test:
     * Wrestler::fakeRetirementModel(MockWrestlerRetirement::class);
     *
     * // Or for customization:
     * Wrestler::fakeRetirementModel(CustomRetirementModel::class);
     * ```
     *
     * @see resolveRetirementModelClass() For the automatic resolution logic
     */
    public static function fakeRetirementModel(string $class): void
    {
        self::cacheRelatedModel('Retirement', $class);
    }
}
