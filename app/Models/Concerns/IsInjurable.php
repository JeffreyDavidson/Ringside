<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Contracts\Injurable;
use App\Models\Lifecycle\Injury;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @template TModel of Model
 *
 * @phpstan-require-implements Injurable<TModel>
 */
trait IsInjurable
{
    use HasLifecycleTransitions;

    /** @return MorphMany<Injury, TModel> */
    public function injuries(): MorphMany
    {
        /** @var MorphMany<Injury, TModel> $relation */
        $relation = $this->morphMany(Injury::class, 'injurable');

        return $relation;
    }

    /** @return MorphOne<Injury, TModel> */
    public function currentInjury(): MorphOne
    {
        /** @var MorphOne<Injury, TModel> $relation */
        $relation = $this->morphOne(Injury::class, 'injurable')
            ->whereNull('ended_at');

        return $relation;
    }

    /** @return MorphMany<Injury, TModel> */
    public function previousInjuries(): MorphMany
    {
        /** @var MorphMany<Injury, TModel> $relation */
        $relation = $this->morphMany(Injury::class, 'injurable')
            ->whereNotNull('ended_at');

        return $relation;
    }

    /** @return MorphOne<Injury, TModel> */
    public function previousInjury(): MorphOne
    {
        /** @var MorphOne<Injury, TModel> $relation */
        $relation = $this->morphOne(Injury::class, 'injurable')
            ->whereNotNull('ended_at')
            ->ofMany('ended_at', 'max');

        return $relation;
    }

    public function isInjured(): bool
    {
        return $this->currentInjury()->exists();
    }
}
