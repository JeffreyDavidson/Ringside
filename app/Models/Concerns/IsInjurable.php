<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Contracts\Injurable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use RuntimeException;

/**
 * Adds injury-related behavior to a model.
 *
 * This trait provides a complete injury system for Eloquent models, including
 * methods to manage current injuries, historical injuries, and injury status.
 * It automatically resolves the related injury model class based on naming conventions.
 *
 * @template TInjury of Model The injury model class (e.g., WrestlerInjury)
 * @template TModel of Model The parent model class that can be injured (e.g., Wrestler)
 *
 * @phpstan-require-implements Injurable<TInjury, TModel>
 *
 * @see Injurable
 *
 * @example
 * ```php
 * // In your model:
 * class Wrestler extends Model implements Injurable
 * {
 *     use IsInjurable;
 * }
 *
 * // Usage:
 * $wrestler = Wrestler::find(1);
 * $wrestler->isInjured();              // Check if currently injured
 * $wrestler->currentInjury();          // Get active injury
 * $wrestler->previousInjuries();       // Get completed injuries
 * ```
 */
trait IsInjurable
{
    use ResolvesRelatedModels;

    /**
     * Get all injuries for the model.
     *
     * This method returns a HasMany relationship that includes all injury records
     * for the model, regardless of their status (active, healed, etc.).
     *
     * @return HasMany<TInjury, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $allInjuries = $wrestler->injuries;
     * $injuryCount = $wrestler->injuries()->count();
     * ```
     */
    public function injuries(): HasMany
    {
        /** @var HasMany<TInjury, TModel> $relation */
        $relation = $this->hasMany($this->resolveInjuryModelClass());

        return $relation;
    }

    /**
     * Get the current (active) injury.
     *
     * Returns a HasOne relationship for the currently active injury.
     * An active injury is one where the 'ended_at' field is null.
     *
     * @return HasOne<TInjury, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $currentInjury = $wrestler->currentInjury;
     *
     * if ($wrestler->currentInjury()->exists()) {
     *     echo "Wrestler is currently injured";
     * }
     * ```
     */
    public function currentInjury(): HasOne
    {
        /** @var HasOne<TInjury, TModel> $relation */
        $relation = $this->hasOne($this->resolveInjuryModelClass())
            ->whereNull('ended_at');

        return $relation;
    }

    /**
     * Get all completed injuries.
     *
     * Returns a HasMany relationship for injuries that have healed.
     * A completed injury is one where the 'ended_at' field is not null.
     *
     * @return HasMany<TInjury, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $healedInjuries = $wrestler->previousInjuries;
     * $injuryHistory = $wrestler->previousInjuries()->orderBy('ended_at', 'desc')->get();
     * ```
     */
    public function previousInjuries(): HasMany
    {
        /** @var HasMany<TInjury, TModel> $relation */
        $relation = $this->hasMany($this->resolveInjuryModelClass())
            ->whereNotNull('ended_at');

        return $relation;
    }

    /**
     * Get the most recent completed injury.
     *
     * Returns a HasOne relationship for the most recently healed injury,
     * determined by the highest 'ended_at' value.
     *
     * @return HasOne<TInjury, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $lastInjury = $wrestler->previousInjury;
     *
     * if ($wrestler->previousInjury()->exists()) {
     *     $healedDate = $wrestler->previousInjury->ended_at;
     * }
     * ```
     */
    public function previousInjury(): HasOne
    {
        /** @var HasOne<TInjury, TModel> $relation */
        $relation = $this->hasOne($this->resolveInjuryModelClass())
            ->whereNotNull('ended_at')
            ->ofMany('ended_at', 'max');

        return $relation;
    }

    /**
     * Determine if the model is currently injured.
     *
     * Checks if there is an active injury (one with a null 'ended_at' field).
     * This is a convenience method that's more efficient than loading the full
     * relationship just to check existence.
     *
     * @return bool True if the model is currently injured, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->isInjured()) {
     *     echo "Cannot book this wrestler - they are injured";
     * }
     * ```
     */
    public function isInjured(): bool
    {
        return $this->currentInjury()->exists();
    }

    /**
     * Determine if the model has any injuries at all.
     *
     * Checks if there are any injury records associated with this model,
     * regardless of their status (active or healed). This is useful for
     * determining if a model has an injury history.
     *
     * @return bool True if the model has any injuries, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->hasInjuries()) {
     *     echo "This wrestler has an injury history";
     * }
     * ```
     */
    public function hasInjuries(): bool
    {
        return $this->injuries()->exists();
    }

    /**
     * Resolve the model class for the injury relation.
     *
     * This method automatically determines the injury model class based on naming
     * conventions. For example, if the parent model is 'Wrestler', it will look for
     * a 'WrestlerInjury' model class.
     *
     * The resolution can be overridden by calling the fakeInjuryModel() method (useful for testing).
     *
     * @return class-string<TInjury> The fully qualified class name of the injury model
     *
     * @throws RuntimeException If the resolved model class doesn't exist
     *
     * @see fakeInjuryModel() For overriding the resolved model class
     *
     * @example
     * For a 'Wrestler' model, this will resolve to 'App\\Models\\Wrestlers\\WrestlerInjury'
     */
    protected function resolveInjuryModelClass(): string
    {
        return $this->resolveRelatedModelClass('Injury');
    }

    /**
     * Override the resolved model class for testing or customization.
     *
     * This method allows you to override the automatic model class resolution,
     * which is particularly useful for testing scenarios where you might want
     * to use a different model class or mock.
     *
     * @param  class-string<TInjury>  $class  The fully qualified class name to use
     *
     * @example
     * ```php
     * // In a test:
     * Wrestler::fakeInjuryModel(MockWrestlerInjury::class);
     *
     * // Or for customization:
     * Wrestler::fakeInjuryModel(CustomInjuryModel::class);
     * ```
     *
     * @see resolveInjuryModelClass() For the automatic resolution logic
     */
    public static function fakeInjuryModel(string $class): void
    {
        self::cacheRelatedModel('Injury', $class);
    }
}
