<?php

declare(strict_types=1);

namespace App\Models\Concerns;

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
        $relation = $this->morphOne(Employment::class, 'employable')
            ->whereNull('ended_at')
            ->where('started_at', '<=', now());

        return $relation;
    }

    /** @return MorphOne<Employment, TModel> */
    public function futureEmployment(): MorphOne
    {
        /** @var MorphOne<Employment, TModel> $relation */
        $relation = $this->morphOne(Employment::class, 'employable')
            ->whereNull('ended_at')
            ->where('started_at', '>', now());

        return $relation;
    }

    /** @return MorphMany<Employment, TModel> */
    public function previousEmployments(): MorphMany
    {
        /** @var MorphMany<Employment, TModel> $relation */
        $relation = $this->morphMany(Employment::class, 'employable')
            ->whereNotNull('ended_at');

        return $relation;
    }

    /** @return MorphOne<Employment, TModel> */
    public function previousEmployment(): MorphOne
    {
        /** @var MorphOne<Employment, TModel> $relation */
        $relation = $this->morphOne(Employment::class, 'employable')
            ->whereNotNull('ended_at')
            ->ofMany('ended_at', 'max');

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

    public function isEmployed(): bool
    {
        return $this->currentEmployment()->exists();
    }

    public function hasFutureEmployment(): bool
    {
        return $this->futureEmployment()->exists();
    }

    public function hasNoCurrentOrFutureEmployment(): bool
    {
        return ! $this->isEmployed() && ! $this->hasFutureEmployment();
    }

    public function isReleased(): bool
    {
        return ! $this->isEmployed() && ! $this->isRetired() && $this->previousEmployments()->exists();
    }

    public function hasEmploymentHistory(): bool
    {
        return $this->employments()->exists();
    }
}
