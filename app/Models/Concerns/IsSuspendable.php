<?php

declare(strict_types=1);

namespace App\Models\Concerns;

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
        $relation = $this->morphOne(Suspension::class, 'suspendable')
            ->whereNull('ended_at');

        return $relation;
    }

    /** @return MorphMany<Suspension, TModel> */
    public function previousSuspensions(): MorphMany
    {
        /** @var MorphMany<Suspension, TModel> $relation */
        $relation = $this->morphMany(Suspension::class, 'suspendable')
            ->whereNotNull('ended_at');

        return $relation;
    }

    /** @return MorphOne<Suspension, TModel> */
    public function previousSuspension(): MorphOne
    {
        /** @var MorphOne<Suspension, TModel> $relation */
        $relation = $this->morphOne(Suspension::class, 'suspendable')
            ->whereNotNull('ended_at')
            ->ofMany('ended_at', 'max');

        return $relation;
    }

    public function isSuspended(): bool
    {
        return $this->currentSuspension()->exists();
    }
}
