<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Contracts\Employable;
use App\Models\Lifecycle\Employment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Adds employment-related behavior to a model.
 *
 * This trait provides a complete employment system for Eloquent models, including
 * methods to manage current employments, historical employments, and employment status.
 * Every employable model persists its history through the shared Employment model.
 *
 * @template TModel of Model The parent model class that can be employed (e.g., Wrestler)
 *
 * @phpstan-require-implements Employable<TModel>
 *
 * @see Employable
 *
 * @example
 * ```php
 * // In your model:
 * class Wrestler extends Model implements Employable
 * {
 *     use IsEmployable;
 * }
 *
 * // Usage:
 * $wrestler = Wrestler::find(1);
 * $wrestler->isEmployed();     // Check if currently employed
 * $wrestler->currentEmployment();       // Get active employment
 * $wrestler->previousEmployments();     // Get completed employments
 * ```
 */
trait IsEmployable
{
    use HasLifecycleTransitions;

    /**
     * Get all employments for the model.
     *
     * This method returns a polymorphic relationship that includes all employment records
     * for the model, regardless of their status (active, completed, etc.).
     *
     * @return MorphMany<Employment, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $allEmployments = $wrestler->employments;
     * $employmentCount = $wrestler->employments()->count();
     * ```
     */
    public function employments(): MorphMany
    {
        /** @var MorphMany<Employment, TModel> $relation */
        $relation = $this->morphMany(Employment::class, 'employable');

        return $relation;
    }

    /**
     * Get the current (active) employment.
     *
     * Returns a HasOne relationship for the currently active employment.
     * An active employment is one where the 'ended_at' field is null.
     *
     * @return MorphOne<Employment, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $currentEmployment = $wrestler->currentEmployment;
     *
     * if ($wrestler->currentEmployment()->exists()) {
     *     echo "Wrestler is currently employed";
     * }
     * ```
     */
    public function currentEmployment(): MorphOne
    {
        /** @var MorphOne<Employment, TModel> $relation */
        $relation = $this->morphOne(Employment::class, 'employable')
            ->whereNull('ended_at')
            ->where('started_at', '<=', now());

        return $relation;
    }

    /**
     * Get a future employment that hasn't started yet.
     *
     * Returns a HasOne relationship for an employment that is scheduled for the future.
     * A future employment has a 'started_at' date greater than now and 'ended_at' is null.
     *
     * @return MorphOne<Employment, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $futureEmployment = $wrestler->futureEmployment;
     *
     * if ($wrestler->futureEmployment()->exists()) {
     *     echo "Wrestler has a scheduled employment";
     * }
     * ```
     */
    public function futureEmployment(): MorphOne
    {
        /** @var MorphOne<Employment, TModel> $relation */
        $relation = $this->morphOne(Employment::class, 'employable')
            ->whereNull('ended_at')
            ->where('started_at', '>', now());

        return $relation;
    }

    /**
     * Get all completed employments.
     *
     * Returns a HasMany relationship for employments that have ended.
     * A completed employment is one where the 'ended_at' field is not null.
     *
     * @return MorphMany<Employment, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $completedEmployments = $wrestler->previousEmployments;
     * $employmentHistory = $wrestler->previousEmployments()->orderBy('ended_at', 'desc')->get();
     * ```
     */
    public function previousEmployments(): MorphMany
    {
        /** @var MorphMany<Employment, TModel> $relation */
        $relation = $this->morphMany(Employment::class, 'employable')
            ->whereNotNull('ended_at');

        return $relation;
    }

    /**
     * Get the most recent completed employment.
     *
     * Returns a HasOne relationship for the most recently completed employment,
     * determined by the highest 'ended_at' value.
     *
     * @return MorphOne<Employment, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $lastEmployment = $wrestler->previousEmployment;
     *
     * if ($wrestler->previousEmployment()->exists()) {
     *     $endDate = $wrestler->previousEmployment->ended_at;
     * }
     * ```
     */
    public function previousEmployment(): MorphOne
    {
        /** @var MorphOne<Employment, TModel> $relation */
        $relation = $this->morphOne(Employment::class, 'employable')
            ->whereNotNull('ended_at')
            ->ofMany('ended_at', 'max');

        return $relation;
    }

    /**
     * Get the earliest employment record.
     *
     * Returns a HasOne relationship for the earliest employment based on 'started_at'.
     *
     * @return MorphOne<Employment, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $firstEmployment = $wrestler->firstEmployment;
     *
     * if ($wrestler->firstEmployment()->exists()) {
     *     $startDate = $wrestler->firstEmployment->started_at;
     * }
     * ```
     */
    public function firstEmployment(): MorphOne
    {
        /** @var MorphOne<Employment, TModel> $relation */
        $relation = $this->morphOne(Employment::class, 'employable')
            ->ofMany('started_at', 'min');

        return $relation;
    }

    /**
     * Determine if the model is currently employed.
     *
     * Checks if there is an active employment (one with a null 'ended_at' field).
     * This is a convenience method that's more efficient than loading the full
     * relationship just to check existence.
     *
     * @return bool True if the model is currently employed, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->isEmployed()) {
     *     echo "This wrestler is available for booking";
     * }
     * ```
     */
    public function isEmployed(): bool
    {
        return $this->currentEmployment()->exists();
    }

    /**
     * Determine if the model has a future employment scheduled.
     *
     * Checks if there is a scheduled employment that hasn't started yet.
     *
     * @return bool True if the model has a future employment, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->hasFutureEmployment()) {
     *     echo "This wrestler has a scheduled employment";
     * }
     * ```
     */
    public function hasFutureEmployment(): bool
    {
        return $this->futureEmployment()->exists();
    }

    /**
     * Determine if the model is not currently employed.
     *
     * Considers a model as not employed if they are explicitly marked
     * as unemployed, released, or retired.
     *
     * @return bool True if the model is not in employment, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->isNotInEmployment()) {
     *     echo "Wrestler is not currently available";
     * }
     * ```
     */
    public function isNotInEmployment(): bool
    {
        return ! $this->isEmployed() && ! $this->hasFutureEmployment();
    }

    /**
     * Determine if the model is currently released.
     *
     * Checks if the model has been released from employment.
     * A released entity cannot be booked and requires re-employment to be active again.
     *
     * @return bool True if the model is released, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->isReleased()) {
     *     echo "This wrestler has been released";
     * }
     * ```
     */
    public function isReleased(): bool
    {
        return ! $this->isEmployed() && ! $this->isRetired() && $this->previousEmployments()->exists();
    }

    /**
     * Check if the entity has any employment history records.
     *
     * This method determines whether the entity has ever been employed
     * by checking for the existence of any employment records, regardless
     * of their current status (active, ended, etc.).
     *
     * @return bool True if any employment history exists, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->hasEmploymentHistory()) {
     *     echo "This wrestler has been employed before";
     * } else {
     *     echo "This wrestler has never been employed";
     * }
     *
     * // Used in business logic validation
     * public function canBeRetired(): bool
     * {
     *     return $this->isEmployed() || $this->hasEmploymentHistory();
     * }
     * ```
     */
    public function hasEmploymentHistory(): bool
    {
        return $this->employments()->exists();
    }
}
