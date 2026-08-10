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
 * @template TActivityPeriod of Model The activity period model class (e.g., TitleActivityPeriod)
 * @template TModel of Model The parent model class that can have activity periods (e.g., Title)
 *
 * @phpstan-require-implements HasActivityPeriodsContract<TActivityPeriod, TModel>
 *
 * @see HasActivityPeriodsContract
 */
trait HasActivityPeriods
{
    /** @use HasEnumStatus<ActivationStatus> */
    use HasEnumStatus;

    use ResolvesRelatedModels;

    /** @return HasMany<TActivityPeriod, TModel> */
    public function activityPeriods(): HasMany
    {
        /** @var HasMany<TActivityPeriod, TModel> $relation */
        $relation = $this->hasMany($this->resolveActivityPeriodModelClass());

        return $relation;
    }

    /**
     * Alias for activityPeriods relationship for backward compatibility.
     *
     * @return HasMany<TActivityPeriod, TModel>
     */
    public function activations(): HasMany
    {
        return $this->activityPeriods();
    }

    /**
     * Get the current activity period, which has started and has not ended.
     *
     * @return HasOne<TActivityPeriod, TModel>
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
     * Get a future activity period that has not started or ended.
     *
     * @return HasOne<TActivityPeriod, TModel>
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
     * Get activity periods that have ended.
     *
     * @return HasMany<TActivityPeriod, TModel>
     */
    public function previousActivityPeriods(): HasMany
    {
        /** @var HasMany<TActivityPeriod, TModel> $relation */
        $relation = $this->hasMany($this->resolveActivityPeriodModelClass())
            ->whereNotNull('ended_at');

        return $relation;
    }

    /**
     * Get the most recently ended activity period.
     *
     * @return HasOne<TActivityPeriod, TModel>
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
     * Get the earliest activity period by start date.
     *
     * @return HasOne<TActivityPeriod, TModel>
     */
    public function firstActivityPeriod(): HasOne
    {
        /** @var HasOne<TActivityPeriod, TModel> $relation */
        $relation = $this->hasOne($this->resolveActivityPeriodModelClass())
            ->ofMany('started_at', 'min');

        return $relation;
    }

    public function hasActivityPeriods(): bool
    {
        return $this->activityPeriods()->exists();
    }

    public function isCurrentlyActive(): bool
    {
        return $this->currentActivityPeriod()->exists();
    }

    public function hasFutureActivity(): bool
    {
        return $this->futureActivityPeriod()->exists();
    }

    public function isNotCurrentlyActive(): bool
    {
        if ($this->isInactive()) {
            return true;
        }

        if ($this->hasFutureActivity()) {
            return true;
        }

        return $this instanceof Retirable && $this->isRetired();
    }

    public function isUnactivated(): bool
    {
        return ! $this->hasActivityPeriods();
    }

    public function isInactive(): bool
    {
        return ! $this->isCurrentlyActive();
    }

    /**
     * Check if the current activity period started on a specific date.
     *
     * @param  Carbon  $activityDate  The date to check against
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
     */
    public function wasActiveBefore(Carbon $activityDate): bool
    {
        $currentPeriod = $this->currentActivityPeriod;

        return $currentPeriod ? $currentPeriod->started_at->lte($activityDate) : false;
    }

    public function hasFutureActivation(): bool
    {
        return $this->futureActivityPeriod()->exists();
    }

    /**
     * Get the formatted start date of the first activity period.
     *
     * Returns 'TBD' if no activity periods exist or the date is unavailable.
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
     * @throws RuntimeException
     * @return class-string<TActivityPeriod>
     */
    protected function resolveActivityPeriodModelClass(): string
    {
        return $this->resolveRelatedModelClass('ActivityPeriod');
    }

    protected function getActivityPeriodTableName(): string
    {
        $modelClass = $this->resolveActivityPeriodModelClass();
        $model = new $modelClass();

        return $model->getTable();
    }
}
