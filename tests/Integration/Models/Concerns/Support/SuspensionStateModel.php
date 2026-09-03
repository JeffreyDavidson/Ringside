<?php

declare(strict_types=1);

namespace Tests\Integration\Models\Concerns\Support;

use App\Models\Concerns\IsSuspendable;
use App\Models\Contracts\Suspendable;
use App\Models\Lifecycle\Suspension;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use JMac\Testing\Double;

/** @implements Suspendable<self> */
final class SuspensionStateModel extends Model implements Suspendable
{
    /** @use IsSuspendable<self> */
    use IsSuspendable;

    public bool $currentSuspensionExists = false;

    /** @return MorphOne<Suspension, self> */
    public function currentSuspension(): MorphOne
    {
        return $this->suspensionHasOne($this->currentSuspensionExists);
    }

    /** @return MorphMany<Suspension, self> */
    public function suspensions(): MorphMany
    {
        return new MorphMany($this->suspensionBuilder(false), new self(), 'suspendable_type', 'suspendable_id', 'id');
    }

    /** @return MorphOne<Suspension, self> */
    private function suspensionHasOne(bool $exists): MorphOne
    {
        return new MorphOne($this->suspensionBuilder($exists), new self(), 'suspendable_type', 'suspendable_id', 'id');
    }

    /** @return LifecycleStateBuilder<Suspension> */
    private function suspensionBuilder(bool $exists): LifecycleStateBuilder
    {
        $query = Double::for(QueryBuilder::class);
        $query->expects('exists')->returns($exists);

        return new LifecycleStateBuilder($query, new Suspension());
    }
}
