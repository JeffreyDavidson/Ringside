<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\IsRetirable;
use App\Models\Contracts\Retirable;
use App\Models\Lifecycle\Retirement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use JMac\Testing\Double;

/** @implements Retirable<self> */
final class RetirementStateModel extends Model implements Retirable
{
    /** @use IsRetirable<self> */
    use IsRetirable;

    public bool $currentRetirementExists = false;

    /** @return MorphOne<Retirement, self> */
    public function currentRetirement(): MorphOne
    {
        return $this->retirementHasOne($this->currentRetirementExists);
    }

    /** @return MorphMany<Retirement, self> */
    public function retirements(): MorphMany
    {
        return new MorphMany($this->retirementBuilder(false), new self(), 'retirable_type', 'retirable_id', 'id');
    }

    /** @return MorphOne<Retirement, self> */
    private function retirementHasOne(bool $exists): MorphOne
    {
        return new MorphOne($this->retirementBuilder($exists), new self(), 'retirable_type', 'retirable_id', 'id');
    }

    /** @return LifecycleStateBuilder<Retirement> */
    private function retirementBuilder(bool $exists): LifecycleStateBuilder
    {
        $query = Double::for(QueryBuilder::class);
        $query->expects('exists')->returns($exists);

        return new LifecycleStateBuilder($query, new Retirement());
    }
}
