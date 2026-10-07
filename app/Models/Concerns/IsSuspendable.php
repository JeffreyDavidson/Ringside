<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Builders\Lifecycle\LifecyclePeriodBuilder;
use App\Lifecycle\LifecycleStateReader;
use App\Models\Contracts\Suspendable;
use App\Models\Lifecycle\Suspension;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @template TModel of Model
 *
 * @phpstan-require-implements Suspendable<TModel>
 */
trait IsSuspendable
{
    use HasLifecycleTransitions;

    /** @return MorphMany<Suspension, TModel> */
    public function suspensions(): MorphMany
    {
        /** @var MorphMany<Suspension, TModel> $relation */
        $relation = $this->morphMany(Suspension::class, 'suspendable');

        return $relation;
    }

    /** @return MorphOne<Suspension, TModel> */
    public function currentSuspension(): MorphOne
    {
        /** @var MorphOne<Suspension, TModel> $relation */
        $relation = $this->morphOne(Suspension::class, 'suspendable');
        LifecyclePeriodBuilder::constrainToOpen($relation->getQuery());

        return $relation;
    }

    /**
     * Determine whether a current suspension exists, reusing the `AVAILABILITY_STATE`
     * projection when the model was loaded with it.
     */
    public function isSuspended(): bool
    {
        return LifecycleStateReader::readProjectedBoolean(
            $this,
            'availability_current_suspension_exists',
            fn (): bool => $this->currentSuspension()->exists(),
        );
    }

    /** @return MorphMany<Suspension, TModel> */
    public function previousSuspensions(): MorphMany
    {
        /** @var MorphMany<Suspension, TModel> $relation */
        $relation = $this->morphMany(Suspension::class, 'suspendable');
        LifecyclePeriodBuilder::constrainToEnded($relation->getQuery());

        return $relation;
    }

    /** @return MorphOne<Suspension, TModel> */
    public function previousSuspension(): MorphOne
    {
        /** @var MorphOne<Suspension, TModel> $relation */
        $relation = $this->morphOne(Suspension::class, 'suspendable');
        LifecyclePeriodBuilder::constrainToEnded($relation->getQuery());
        $relation->ofMany('ended_at', 'max');

        return $relation;
    }
}
