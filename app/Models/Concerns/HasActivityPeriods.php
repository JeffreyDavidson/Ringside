<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Shared\ActivationStatus;
use App\Models\Contracts\HasActivityPeriods as HasActivityPeriodsContract;
use App\Models\Contracts\Retirable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Adds activity period behavior to a model.
 *
 * This trait provides a complete activity period system for Eloquent models, including
 * methods to manage current activity periods, historical activity periods, and activity status.
 * Perfect for entities like titles and stables that can be active/inactive over multiple periods.
 *
 * @template TActivityPeriod of Model The activity period model class (e.g., TitleActivityPeriod)
 * @template TModel of Model The parent model class that can have activity periods (e.g., Title)
 *
 * @phpstan-require-implements HasActivityPeriodsContract<TActivityPeriod, TModel>
 *
 * @see HasActivityPeriodsContract
 *
 * @example
 * ```php
 * // In your model:
 * class Title extends Model implements HasActivityPeriods
 * {
 *     use HasActivityPeriods;
 * }
 *
 * // Usage:
 * $title = Title::find(1);
 * $title->isCurrentlyActive();         // Check if currently active
 * $title->currentActivityPeriod();     // Get active period
 * $title->previousActivityPeriods();   // Get completed periods
 * ```
 */
trait HasActivityPeriods
{
    /** @use HasEnumStatus<ActivationStatus> */
    use HasEnumStatus;

    use ResolvesRelatedModels;

    /**
     * Get all activity periods for the model.
     *
     * This method returns a HasMany relationship that includes all activity period records
     * for the model, regardless of their status (active, completed, etc.).
     *
     * @return HasMany<TActivityPeriod, TModel> The relationship instance
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * $allPeriods = $title->activityPeriods;
     * $periodCount = $title->activityPeriods()->count();
     * ```
     */
    public function activityPeriods(): HasMany
    {
        /** @var HasMany<TActivityPeriod, TModel> $relation */
        $relation = $this->hasMany($this->resolveActivityPeriodModelClass(), null, null, null, $this->getActivityPeriodTableName());

        return $relation;
    }

    /**
     * Alias for activityPeriods relationship for backward compatibility.
     */
    public function activations(): HasMany
    {
        return $this->activityPeriods();
    }

    /**
     * Get the current (active) activity period.
     *
     * Returns a HasOne relationship for the currently active period.
     * An active period is one where the 'ended_at' field is null.
     *
     * @return HasOne<TActivityPeriod, TModel> The relationship instance
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * $currentPeriod = $title->currentActivityPeriod;
     *
     * if ($title->currentActivityPeriod()->exists()) {
     *     echo "Title is currently active";
     * }
     * ```
     */
    public function currentActivityPeriod(): HasOne
    {
        /** @var HasOne<TActivityPeriod, TModel> $relation */
        $relation = $this->hasOne($this->resolveActivityPeriodModelClass())
            ->whereNull('ended_at')
            ->where('started_at', '<=', now());

        return $relation;
    }

    /**
     * Get a future activity period that hasn't started yet.
     *
     * Returns a HasOne relationship for a period that is scheduled for the future.
     * A future period has a 'started_at' date greater than now and 'ended_at' is null.
     *
     * @return HasOne<TActivityPeriod, TModel> The relationship instance
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * $futurePeriod = $title->futureActivityPeriod;
     *
     * if ($title->futureActivityPeriod()->exists()) {
     *     echo "Title has a scheduled activation";
     * }
     * ```
     */
    public function futureActivityPeriod(): HasOne
    {
        /** @var HasOne<TActivityPeriod, TModel> $relation */
        $relation = $this->hasOne($this->resolveActivityPeriodModelClass())
            ->whereNull('ended_at')
            ->where('started_at', '>', now());

        return $relation;
    }

    /**
     * Get all completed activity periods.
     *
     * Returns a HasMany relationship for periods that have ended.
     * A completed period is one where the 'ended_at' field is not null.
     *
     * @return HasMany<TActivityPeriod, TModel> The relationship instance
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * $completedPeriods = $title->previousActivityPeriods;
     * $periodHistory = $title->previousActivityPeriods()->orderBy('ended_at', 'desc')->get();
     * ```
     */
    public function previousActivityPeriods(): HasMany
    {
        /** @var HasMany<TActivityPeriod, TModel> $relation */
        $relation = $this->hasMany($this->resolveActivityPeriodModelClass())
            ->whereNotNull('ended_at');

        return $relation;
    }

    /**
     * Get the most recent completed activity period.
     *
     * Returns a HasOne relationship for the most recently completed period,
     * determined by the highest 'ended_at' value.
     *
     * @return HasOne<TActivityPeriod, TModel> The relationship instance
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * $lastPeriod = $title->previousActivityPeriod;
     *
     * if ($title->previousActivityPeriod()->exists()) {
     *     $endDate = $title->previousActivityPeriod->ended_at;
     * }
     * ```
     */
    public function previousActivityPeriod(): HasOne
    {
        /** @var HasOne<TActivityPeriod, TModel> $relation */
        $relation = $this->hasOne($this->resolveActivityPeriodModelClass())
            ->whereNotNull('ended_at')
            ->latest('ended_at');

        return $relation;
    }

    /**
     * Get the first activity period record.
     *
     * Returns a HasOne relationship for the earliest period based on 'started_at'.
     *
     * @return HasOne<TActivityPeriod, TModel> The relationship instance
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * $firstPeriod = $title->firstActivityPeriod;
     *
     * if ($title->firstActivityPeriod()->exists()) {
     *     $startDate = $title->firstActivityPeriod->started_at;
     * }
     * ```
     */
    public function firstActivityPeriod(): HasOne
    {
        /** @var HasOne<TActivityPeriod, TModel> $relation */
        $relation = $this->hasOne($this->resolveActivityPeriodModelClass())
            ->ofMany('started_at', 'min');

        return $relation;
    }

    /**
     * Determine if the model has any activity periods at all.
     *
     * Checks if there are any activity period records associated with this model,
     * regardless of their status (active or completed).
     *
     * @return bool True if the model has any activity periods, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     *
     * if ($title->hasActivityPeriods()) {
     *     echo "This title has an activity history";
     * }
     * ```
     */
    public function hasActivityPeriods(): bool
    {
        return $this->activityPeriods()->exists();
    }

    /**
     * Determine if the model is currently active.
     *
     * Checks if there is an active period (one with a null 'ended_at' field).
     * This is a convenience method that's more efficient than loading the full
     * relationship just to check existence.
     *
     * @return bool True if the model is currently active, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     *
     * if ($title->isCurrentlyActive()) {
     *     echo "This title is currently active";
     * }
     * ```
     */
    public function isCurrentlyActive(): bool
    {
        return $this->currentActivityPeriod()->exists();
    }

    /**
     * Determine if the model has a future activity period scheduled.
     *
     * Checks if there is a scheduled period that hasn't started yet.
     *
     * @return bool True if the model has a future period, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     *
     * if ($title->hasFutureActivity()) {
     *     echo "This title has a scheduled activation";
     * }
     * ```
     */
    public function hasFutureActivity(): bool
    {
        return $this->futureActivityPeriod()->exists();
    }

    /**
     * Check if the model is not currently active.
     *
     * Considers the model not active if it is inactive, scheduled for the future,
     * or retired (if the model supports retirement).
     *
     * @return bool True if the model is not currently active, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     *
     * if ($title->isNotCurrentlyActive()) {
     *     echo "Title is not currently available";
     * }
     * ```
     */
    public function isNotCurrentlyActive(): bool
    {
        if ($this->isInactive()) {
            return true;
        }

        if ($this->hasFutureActivity()) {
            return true;
        }

        // Check if the model is retired (assuming it implements IsRetirable)
        return $this instanceof Retirable && $this->isRetired();
    }

    /**
     * Check if the model's status is Unactivated.
     *
     * @return bool True if the status is Unactivated, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     *
     * if ($title->isUnactivated()) {
     *     echo "This title has never been activated";
     * }
     * ```
     */
    public function isUnactivated(): bool
    {
        return ! $this->hasActivityPeriods();
    }

    /**
     * Check if the model's status is Inactive.
     *
     * @return bool True if the status is Inactive, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     *
     * if ($title->isInactive()) {
     *     echo "This title is currently inactive";
     * }
     * ```
     */
    public function isInactive(): bool
    {
        return ! $this->isCurrentlyActive();
    }

    /**
     * Check if the current activity period started on a specific date.
     *
     * @param  Carbon  $activityDate  The date to check against
     * @return bool True if activity started on the specified date, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * $targetDate = Carbon::parse('2024-01-15');
     *
     * if ($title->wasActiveOn($targetDate)) {
     *     echo "Title became active on January 15, 2024";
     * }
     * ```
     */
    public function wasActiveOn(Carbon $activityDate): bool
    {
        $currentPeriod = $this->currentActivityPeriod;

        return $currentPeriod ? $currentPeriod->started_at->isSameDay($activityDate) : false;
    }

    /**
     * Check if the current activity period started on or before a specific date.
     *
     * @param  Carbon  $activityDate  The date to check against
     * @return bool True if activity started before or on the specified date, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * $targetDate = Carbon::parse('2024-01-15');
     *
     * if ($title->wasActiveBefore($targetDate)) {
     *     echo "Title was active before January 15, 2024";
     * }
     * ```
     */
    public function wasActiveBefore(Carbon $activityDate): bool
    {
        $currentPeriod = $this->currentActivityPeriod;

        return $currentPeriod ? $currentPeriod->started_at->lte($activityDate) : false;
    }

    /**
     * Check if the model has a future activation scheduled.
     *
     * A future activation is an activity period that is scheduled to start
     * in the future (started_at > now) and has not ended (ended_at is null).
     *
     * @return bool True if has future activation, false otherwise
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * if ($title->hasFutureActivation()) {
     *     echo "Title has a scheduled activation";
     * }
     * ```
     */
    public function hasFutureActivation(): bool
    {
        return $this->futureActivityPeriod()->exists();
    }

    /**
     * Get the formatted start date of the first activity period.
     *
     * Returns 'TBD' if no activity periods exist or the date is unavailable.
     *
     * @return string The formatted date (Y-m-d) or 'TBD'
     *
     * @example
     * ```php
     * $title = Title::find(1);
     * echo $title->getFormattedFirstActivity(); // "2024-01-15" or "TBD"
     * ```
     */
    public function getFormattedFirstActivity(): string
    {
        if (! $this->hasActivityPeriods()) {
            return 'TBD';
        }

        $firstPeriod = $this->firstActivityPeriod;

        return $firstPeriod?->started_at?->format('Y-m-d') ?? 'TBD';
    }

    /**
     * Resolve the model class for the activity period relation.
     *
     * This method automatically determines the activity period model class based on naming
     * conventions. For example, if the parent model is 'Title', it will look for
     * a 'TitleActivityPeriod' model class.
     *
     * @return class-string<TActivityPeriod> The fully qualified class name of the activity period model
     *
     * @throws RuntimeException If the resolved model class doesn't exist
     *
     * @see fakeActivityPeriodModel() For overriding the resolved model class
     *
     * @example
     * For a 'Title' model, this will resolve to 'App\\Models\\Titles\\TitleActivityPeriod'
     */
    protected function resolveActivityPeriodModelClass(): string
    {
        return $this->resolveRelatedModelClass('ActivityPeriod');
    }

    /**
     * Get the table name for activity periods.
     *
     * This method can be overridden in models to specify a custom table name
     * for activity periods. By default, it uses the resolved model's table name.
     *
     * @return string The table name for activity periods
     */
    protected function getActivityPeriodTableName(): string
    {
        $modelClass = $this->resolveActivityPeriodModelClass();
        $model = new $modelClass();

        return $model->getTable();
    }

    /**
     * Override the resolved model class for testing or customization.
     *
     * This method allows you to override the automatic model class resolution,
     * which is particularly useful for testing scenarios where you might want
     * to use a different model class or mock.
     *
     * @param  class-string<TActivityPeriod>  $class  The fully qualified class name to use
     *
     * @example
     * ```php
     * // In a test:
     * Title::fakeActivityPeriodModel(MockTitleActivityPeriod::class);
     *
     * // Or for customization:
     * Title::fakeActivityPeriodModel(CustomActivityPeriodModel::class);
     * ```
     *
     * @see resolveActivityPeriodModelClass() For the automatic resolution logic
     */
    public static function fakeActivityPeriodModel(string $class): void
    {
        self::cacheRelatedModel('ActivityPeriod', $class);
    }
}
