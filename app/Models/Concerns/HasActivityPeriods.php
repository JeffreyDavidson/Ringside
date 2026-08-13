<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Enums\Shared\ActivationStatus;
use App\Models\Contracts\HasActivityPeriods as HasActivityPeriodsContract;
use App\Models\Contracts\Retirable;
use App\Models\Lifecycle\ActivityPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Carbon;

/**
 * @template TModel of Model The parent model class that can have activity periods (e.g., Title)
 *
 * @phpstan-require-implements HasActivityPeriodsContract<TModel>
 *
 * @see HasActivityPeriodsContract
 */
trait HasActivityPeriods
{
    /** @use HasEnumStatus<ActivationStatus> */
    use HasEnumStatus;

    /** @return MorphMany<ActivityPeriod, TModel> */
    public function activityPeriods(): MorphMany
    {
        /** @var MorphMany<ActivityPeriod, TModel> $relation */
        $relation = $this->morphMany(ActivityPeriod::class, 'activeable');

        return $relation;
    }

    /**
     * Get the current activity period, which has started and has not ended.
     *
     * @return MorphOne<ActivityPeriod, TModel>
     */
    public function currentActivityPeriod(): MorphOne
    {
        /** @var MorphOne<ActivityPeriod, TModel> $relation */
        $relation = $this->morphOne(ActivityPeriod::class, 'activeable')
            ->whereNull('ended_at')
            ->where('started_at', '<=', now());

        return $relation;
    }

    /**
     * Get a future activity period that has not started or ended.
     *
     * @return MorphOne<ActivityPeriod, TModel>
     */
    public function futureActivityPeriod(): MorphOne
    {
        /** @var MorphOne<ActivityPeriod, TModel> $relation */
        $relation = $this->morphOne(ActivityPeriod::class, 'activeable')
            ->whereNull('ended_at')
            ->where('started_at', '>', now());

        return $relation;
    }

    /**
     * Get activity periods that have ended.
     *
     * @return MorphMany<ActivityPeriod, TModel>
     */
    public function previousActivityPeriods(): MorphMany
    {
        /** @var MorphMany<ActivityPeriod, TModel> $relation */
        $relation = $this->morphMany(ActivityPeriod::class, 'activeable')
            ->whereNotNull('ended_at');

        return $relation;
    }

    /**
     * Get the most recently ended activity period.
     *
     * @return MorphOne<ActivityPeriod, TModel>
     */
    public function previousActivityPeriod(): MorphOne
    {
        /** @var MorphOne<ActivityPeriod, TModel> $relation */
        $relation = $this->morphOne(ActivityPeriod::class, 'activeable')
            ->whereNotNull('ended_at')
            ->latest('ended_at');

        return $relation;
    }

    /**
     * Get the earliest activity period by start date.
     *
     * @return MorphOne<ActivityPeriod, TModel>
     */
    public function firstActivityPeriod(): MorphOne
    {
        /** @var MorphOne<ActivityPeriod, TModel> $relation */
        $relation = $this->morphOne(ActivityPeriod::class, 'activeable')
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

    protected function getActivityPeriodTableName(): string
    {
        return (new ActivityPeriod())->getTable();
    }
}
