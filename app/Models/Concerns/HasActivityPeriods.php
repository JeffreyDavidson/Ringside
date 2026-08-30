<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Builders\Lifecycle\LifecyclePeriodBuilder;
use App\Models\Contracts\HasActivityPeriods as HasActivityPeriodsContract;
use App\Models\Lifecycle\ActivityPeriod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @template TModel of Model The parent model class that can have activity periods (e.g., Title)
 *
 * @phpstan-require-implements HasActivityPeriodsContract<TModel>
 *
 * @see HasActivityPeriodsContract
 */
trait HasActivityPeriods
{
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
        $relation = $this->morphOne(ActivityPeriod::class, 'activeable');
        LifecyclePeriodBuilder::constrainToCurrent($relation->getQuery());

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
        $relation = $this->morphOne(ActivityPeriod::class, 'activeable');
        LifecyclePeriodBuilder::constrainToScheduled($relation->getQuery());

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
        $relation = $this->morphMany(ActivityPeriod::class, 'activeable');
        LifecyclePeriodBuilder::constrainToEnded($relation->getQuery());

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
        $relation = $this->morphOne(ActivityPeriod::class, 'activeable');
        LifecyclePeriodBuilder::constrainToEnded($relation->getQuery());
        $relation->latest('ended_at');

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

    public function isCurrentlyActive(): bool
    {
        return $this->currentActivityPeriod()->exists();
    }
}
