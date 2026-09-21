<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Builders\Lifecycle\LifecyclePeriodBuilder;
use App\Models\Contracts\Retirable;
use App\Models\Lifecycle\Retirement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @template TModel of Model
 *
 * @phpstan-require-implements Retirable<TModel>
 */
trait IsRetirable
{
    use HasLifecycleTransitions;

    /** @return MorphMany<Retirement, TModel> */
    public function retirements(): MorphMany
    {
        /** @var MorphMany<Retirement, TModel> $relation */
        $relation = $this->morphMany(Retirement::class, 'retirable');

        return $relation;
    }

    /** @return MorphOne<Retirement, TModel> */
    public function currentRetirement(): MorphOne
    {
        /** @var MorphOne<Retirement, TModel> $relation */
        $relation = $this->morphOne(Retirement::class, 'retirable');
        LifecyclePeriodBuilder::constrainToOpen($relation->getQuery());

        return $relation;
    }

    /** @return MorphMany<Retirement, TModel> */
    public function previousRetirements(): MorphMany
    {
        /** @var MorphMany<Retirement, TModel> $relation */
        $relation = $this->morphMany(Retirement::class, 'retirable');
        LifecyclePeriodBuilder::constrainToEnded($relation->getQuery());

        return $relation;
    }

    /** @return MorphOne<Retirement, TModel> */
    public function previousRetirement(): MorphOne
    {
        /** @var MorphOne<Retirement, TModel> $relation */
        $relation = $this->morphOne(Retirement::class, 'retirable');
        LifecyclePeriodBuilder::constrainToEnded($relation->getQuery());
        $relation->ofMany('ended_at', 'max');

        return $relation;
    }
}
