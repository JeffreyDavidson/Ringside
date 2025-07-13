<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Shared\EmploymentStatus;
use App\Models\Contracts\Employable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Adds employment-related behavior to a model.
 *
 * This trait provides a complete employment system for Eloquent models, including
 * methods to manage current employments, historical employments, and employment status.
 * It automatically resolves the related employment model class based on naming conventions.
 *
 * @template TEmployment of Model The employment model class (e.g., WrestlerEmployment)
 * @template TModel of Model The parent model class that can be employed (e.g., Wrestler)
 *
 * @phpstan-require-implements Employable<TEmployment, TModel>
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
    /** @use HasEnumStatus<EmploymentStatus> */
    use HasEnumStatus;

    use ResolvesRelatedModels;

    /**
     * Get all employments for the model.
     *
     * This method returns a HasMany relationship that includes all employment records
     * for the model, regardless of their status (active, completed, etc.).
     *
     * @return HasMany<TEmployment, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $allEmployments = $wrestler->employments;
     * $employmentCount = $wrestler->employments()->count();
     * ```
     */
    public function employments(): HasMany
    {
        /** @var HasMany<TEmployment, TModel> $relation */
        $relation = $this->hasMany($this->resolveEmploymentModelClass());

        return $relation;
    }

    /**
     * Get the current (active) employment.
     *
     * Returns a HasOne relationship for the currently active employment.
     * An active employment is one where the 'ended_at' field is null.
     *
     * @return HasOne<TEmployment, TModel> The relationship instance
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
    public function currentEmployment(): HasOne
    {
        /** @var HasOne<TEmployment, TModel> $relation */
        $relation = $this->hasOne($this->resolveEmploymentModelClass())
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
     * @return HasOne<TEmployment, TModel> The relationship instance
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
    public function futureEmployment(): HasOne
    {
        /** @var HasOne<TEmployment, TModel> $relation */
        $relation = $this->hasOne($this->resolveEmploymentModelClass())
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
     * @return HasMany<TEmployment, TModel> The relationship instance
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $completedEmployments = $wrestler->previousEmployments;
     * $employmentHistory = $wrestler->previousEmployments()->orderBy('ended_at', 'desc')->get();
     * ```
     */
    public function previousEmployments(): HasMany
    {
        /** @var HasMany<TEmployment, TModel> $relation */
        $relation = $this->hasMany($this->resolveEmploymentModelClass())
            ->whereNotNull('ended_at');

        return $relation;
    }

    /**
     * Get the most recent completed employment.
     *
     * Returns a HasOne relationship for the most recently completed employment,
     * determined by the highest 'ended_at' value.
     *
     * @return HasOne<TEmployment, TModel> The relationship instance
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
    public function previousEmployment(): HasOne
    {
        /** @var HasOne<TEmployment, TModel> $relation */
        $relation = $this->hasOne($this->resolveEmploymentModelClass())
            ->whereNotNull('ended_at')
            ->ofMany('ended_at', 'max');

        return $relation;
    }

    /**
     * Get the earliest employment record.
     *
     * Returns a HasOne relationship for the earliest employment based on 'started_at'.
     *
     * @return HasOne<TEmployment, TModel> The relationship instance
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
    public function firstEmployment(): HasOne
    {
        /** @var HasOne<TEmployment, TModel> $relation */
        $relation = $this->hasOne($this->resolveEmploymentModelClass())
            ->ofMany('started_at', 'min');

        return $relation;
    }

    /**
     * Determine if the model has any employments at all.
     *
     * Checks if there are any employment records associated with this model,
     * regardless of their status (active or completed). This is useful for
     * determining if a model has an employment history.
     *
     * @return bool True if the model has any employments, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     *
     * if ($wrestler->hasEmployments()) {
     *     echo "This wrestler has an employment history";
     * }
     * ```
     */
    public function hasEmployments(): bool
    {
        return $this->employments()->exists();
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
     * Check if the model is currently employed (alias for consistency).
     *
     * This method provides a consistent naming convention with other status methods
     * like isInjured(), isSuspended(), isRetired().
     *
     * @return bool True if currently employed, false otherwise
     */
    public function isCurrentlyEmployed(): bool
    {
        return $this->isEmployed();
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
        if ($this->hasAnyStatus([EmploymentStatus::Unemployed, EmploymentStatus::Released])) {
            return true;
        }

        // Check if the model is retired (assuming it implements IsRetirable)
        return $this->isRetired();
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
        return $this->hasStatus(EmploymentStatus::Released);
    }

    /**
     * Check if the current employment started on a specific date.
     *
     * @param  Carbon  $employmentDate  The date to check against
     * @return bool True if employed on the specified date, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $targetDate = Carbon::parse('2024-01-15');
     *
     * if ($wrestler->employmentStartedOn($targetDate)) {
     *     echo "Wrestler was employed exactly on January 15, 2024";
     * }
     * ```
     */
    public function employmentStartedOn(Carbon $employmentDate): bool
    {
        $currentEmployment = $this->currentEmployment;

        return $currentEmployment ? $currentEmployment->started_at->eq($employmentDate) : false;
    }

    /**
     * Check if the current employment started on or before a specific date.
     *
     * @param  Carbon  $employmentDate  The date to check against
     * @return bool True if employed before or on the specified date, false otherwise
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * $targetDate = Carbon::parse('2024-01-15');
     *
     * if ($wrestler->employmentStartedBefore($targetDate)) {
     *     echo "Wrestler was employed before January 15, 2024";
     * }
     * ```
     */
    public function employmentStartedBefore(Carbon $employmentDate): bool
    {
        $currentEmployment = $this->currentEmployment;

        return $currentEmployment ? $currentEmployment->started_at->lte($employmentDate) : false;
    }

    /**
     * Get the formatted start date of the first employment.
     *
     * Returns 'TBD' if no employment exists or the date is unavailable.
     *
     * @return string The formatted date (Y-m-d) or 'TBD'
     *
     * @example
     * ```php
     * $wrestler = Wrestler::find(1);
     * echo $wrestler->getFormattedFirstEmployment(); // "2024-01-15" or "TBD"
     * ```
     */
    public function getFormattedFirstEmployment(): string
    {
        if (! $this->hasEmployments()) {
            return 'TBD';
        }

        $firstEmployment = $this->firstEmployment;

        return $firstEmployment?->started_at?->format('Y-m-d') ?? 'TBD';
    }

    /**
     * Resolve the model class for the employment relation.
     *
     * This method automatically determines the employment model class based on naming
     * conventions. For example, if the parent model is 'Wrestler', it will look for
     * a 'WrestlerEmployment' model class.
     *
     * The resolution can be overridden by calling the fakeEmploymentModel() method (useful for testing).
     *
     * @return class-string<TEmployment> The fully qualified class name of the employment model
     *
     * @throws RuntimeException If the resolved model class doesn't exist
     *
     * @see fakeEmploymentModel() For overriding the resolved model class
     *
     * @example
     * For a 'Wrestler' model, this will resolve to 'App\\Models\\Wrestlers\\WrestlerEmployment'
     */
    protected function resolveEmploymentModelClass(): string
    {
        return $this->resolveRelatedModelClass('Employment');
    }

    /**
     * Override the resolved model class for testing or customization.
     *
     * This method allows you to override the automatic model class resolution,
     * which is particularly useful for testing scenarios where you might want
     * to use a different model class or mock.
     *
     * @param  class-string<TEmployment>  $class  The fully qualified class name to use
     *
     * @example
     * ```php
     * // In a test:
     * Wrestler::fakeEmploymentModel(MockWrestlerEmployment::class);
     *
     * // Or for customization:
     * Wrestler::fakeEmploymentModel(CustomEmploymentModel::class);
     * ```
     *
     * @see resolveEmploymentModelClass() For the automatic resolution logic
     */
    public static function fakeEmploymentModel(string $class): void
    {
        self::cacheRelatedModel('Employment', $class);
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
