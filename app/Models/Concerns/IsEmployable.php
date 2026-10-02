<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Builders\Lifecycle\LifecyclePeriodBuilder;
use App\Lifecycle\LifecycleStateReader;
use App\Models\Contracts\Employable;
use App\Models\Lifecycle\Employment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @template TModel of Model
 *
 * @phpstan-require-implements Employable<TModel>
 */
trait IsEmployable
{
    use HasLifecycleTransitions;

    /** @return MorphMany<Employment, TModel> */
    public function employments(): MorphMany
    {
        /** @var MorphMany<Employment, TModel> $relation */
        $relation = $this->morphMany(Employment::class, 'employable');

        return $relation;
    }

    /** @return MorphOne<Employment, TModel> */
    public function currentEmployment(): MorphOne
    {
        /** @var MorphOne<Employment, TModel> $relation */
        $relation = $this->morphOne(Employment::class, 'employable');
        LifecyclePeriodBuilder::constrainToCurrent($relation->getQuery());

        return $relation;
    }

    /** @return MorphOne<Employment, TModel> */
    public function futureEmployment(): MorphOne
    {
        /** @var MorphOne<Employment, TModel> $relation */
        $relation = $this->morphOne(Employment::class, 'employable');
        LifecyclePeriodBuilder::constrainToScheduled($relation->getQuery());

        return $relation;
    }

    /**
     * Determine whether a current employment exists, reusing the `withEmploymentStatusState`
     * projection when the model was loaded with it.
     */
    public function hasCurrentEmployment(): bool
    {
        return LifecycleStateReader::readProjectedBoolean(
            $this,
            'status_current_employment_exists',
            fn (): bool => $this->currentEmployment()->exists(),
        );
    }

    /**
     * Determine whether a scheduled employment exists, reusing the `withEmploymentStatusState`
     * projection when the model was loaded with it.
     */
    public function hasFutureEmployment(): bool
    {
        return LifecycleStateReader::readProjectedBoolean(
            $this,
            'status_future_employment_exists',
            fn (): bool => $this->futureEmployment()->exists(),
        );
    }

    /**
     * Determine whether any employment exists, reusing the `withEmploymentStatusState`
     * projection when the model was loaded with it.
     */
    public function hasEmploymentHistory(): bool
    {
        return LifecycleStateReader::readProjectedBoolean(
            $this,
            'status_employments_exists',
            fn (): bool => $this->employments()->exists(),
        );
    }

    /** @return MorphMany<Employment, TModel> */
    public function previousEmployments(): MorphMany
    {
        /** @var MorphMany<Employment, TModel> $relation */
        $relation = $this->morphMany(Employment::class, 'employable');
        LifecyclePeriodBuilder::constrainToEnded($relation->getQuery());

        return $relation;
    }

    /** @return MorphOne<Employment, TModel> */
    public function previousEmployment(): MorphOne
    {
        /** @var MorphOne<Employment, TModel> $relation */
        $relation = $this->morphOne(Employment::class, 'employable');
        LifecyclePeriodBuilder::constrainToEnded($relation->getQuery());
        $relation->ofMany('ended_at', 'max');

        return $relation;
    }

    /** @return MorphOne<Employment, TModel> */
    public function firstEmployment(): MorphOne
    {
        /** @var MorphOne<Employment, TModel> $relation */
        $relation = $this->morphOne(Employment::class, 'employable')
            ->ofMany('started_at', 'min');

        return $relation;
    }
}
