<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LogicException;

/**
 * Adds suspension-related behavior to a model.
 *
 * This trait provides a complete suspension system for Eloquent models, including
 * methods to manage current suspensions, historical suspensions, and suspension status.
 * It automatically resolves the related suspension model class based on naming conventions.
 *
 * @template TSuspension of Model The suspension model class (e.g., WrestlerSuspension)
 * @template TModel of Model The parent model class that can be suspended (e.g., Wrestler)
 *
 * @phpstan-require-implements Suspendable<TSuspension, TModel>
 *
 * @see Suspendable
 *
 * @example
 * ```php
 * // In your model:
 * class Wrestler extends Model implements Suspendable
 * {
 *     use IsSuspendable;
 * }
 *
 * // Usage:
 * $wrestler = Wrestler::find(1);
 * $wrestler->isSuspended();           // Check if currently suspended
 * $wrestler->currentSuspension();     // Get active suspension
 * $wrestler->previousSuspensions();   // Get completed suspensions
 * ```
 */
trait IsSuspendable
{
    use ResolvesRelatedModels;

    /**
     * Get all suspensions for the model.
     *
     * This method returns a HasMany relationship that includes all suspension records
     * for the model, regardless of their status (active, completed, etc.).
     *
     * @return HasMany<TSuspension, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $allSuspensions = $wrestler->suspensions;
     * $suspensionCount = $wrestler->suspensions()->count();
     * ```
     */
    public function suspensions(): HasMany
    {
        /** @var HasMany<TSuspension, TModel> $relation */
        $relation = $this->hasMany($this->resolveSuspensionModelClass());

        return $relation;
    }

    /**
     * Get the current (active) suspension.
     *
     * Returns a HasOne relationship for the currently active suspension.
     * An active suspension is one where the 'ended_at' field is null.
     *
     * @return HasOne<TSuspension, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $currentSuspension = $wrestler->currentSuspension;
     *
     * if ($wrestler->currentSuspension()->exists()) {
     *     echo "Wrestler is currently suspended";
     * }
     * ```
     */
    public function currentSuspension(): HasOne
    {
        /** @var HasOne<TSuspension, TModel> $relation */
        $relation = $this->hasOne($this->resolveSuspensionModelClass())
            ->whereNull('ended_at');

        return $relation;
    }

    /**
     * Get all completed suspensions.
     *
     * Returns a HasMany relationship for suspensions that have ended.
     * A completed suspension is one where the 'ended_at' field is not null.
     *
     * @return HasMany<TSuspension, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $completedSuspensions = $wrestler->previousSuspensions;
     * $suspensionHistory = $wrestler->previousSuspensions()->orderBy('ended_at', 'desc')->get();
     * ```
     */
    public function previousSuspensions(): HasMany
    {
        /** @var HasMany<TSuspension, TModel> $relation */
        $relation = $this->hasMany($this->resolveSuspensionModelClass())
            ->whereNotNull('ended_at');

        return $relation;
    }

    /**
     * Get the most recent completed suspension.
     *
     * Returns a HasOne relationship for the most recently completed suspension,
     * determined by the highest 'ended_at' value.
     *
     * @return HasOne<TSuspension, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $lastSuspension = $wrestler->previousSuspension;
     *
     * if ($wrestler->previousSuspension()->exists()) {
     *     $endDate = $wrestler->previousSuspension->ended_at;
     * }
     * ```
     */
    public function previousSuspension(): HasOne
    {
        /** @var HasOne<TSuspension, TModel> $relation */
        $relation = $this->hasOne($this->resolveSuspensionModelClass())
            ->whereNotNull('ended_at')
            ->ofMany('ended_at', 'max');

        return $relation;
    }

    /**
     * Determine if the model is currently suspended.
     *
     * Checks if there is an active suspension (one with a null 'ended_at' field).
     * This is a convenience method that's more efficient than loading the full
     * relationship just to check existence.
     *
     * @return bool True if the model is currently suspended, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->isSuspended()) {
     *     echo "Cannot book this wrestler - they are suspended";
     * }
     * ```
     */
    public function isSuspended(): bool
    {
        return $this->currentSuspension()->exists();
    }

    /**
     * Determine if the model has any suspensions at all.
     *
     * Checks if there are any suspension records associated with this model,
     * regardless of their status (active or completed). This is useful for
     * determining if a model has a suspension history.
     *
     * @return bool True if the model has any suspensions, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->hasSuspensions()) {
     *     echo "This wrestler has a suspension history";
     * }
     * ```
     */
    public function hasSuspensions(): bool
    {
        return $this->suspensions()->exists();
    }

    /**
     * Resolve the model class for the suspension relation.
     *
     * This method automatically determines the suspension model class based on naming
     * conventions. For example, if the parent model is 'Wrestler', it will look for
     * a 'WrestlerSuspension' model class.
     *
     * @throws LogicException If the resolved model class doesn't exist
     * @return class-string<TSuspension> The fully qualified class name of the suspension model
     *
     * @example
     * For a 'Wrestler' model, this will resolve to 'App\\Models\\Wrestlers\\WrestlerSuspension'
     */
    protected function resolveSuspensionModelClass(): string
    {
        return $this->resolveRelatedModelClass('Suspension');
    }
}
