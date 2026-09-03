<?php

declare(strict_types=1);

namespace Tests\Integration\Models\Concerns\Support;

use App\Models\Concerns\IsEmployable;
use App\Models\Contracts\Employable;
use App\Models\Lifecycle\Employment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use JMac\Testing\Double;

/** @implements Employable<self> */
final class EmploymentStateModel extends Model implements Employable
{
    /** @use IsEmployable<self> */
    use IsEmployable;

    public bool $futureEmploymentExists = false;

    public bool $currentEmploymentExists = false;

    public bool $employmentExists = false;

    /** @return MorphOne<Employment, self> */
    public function futureEmployment(): MorphOne
    {
        return $this->employmentHasOne($this->futureEmploymentExists);
    }

    /** @return MorphOne<Employment, self> */
    public function currentEmployment(): MorphOne
    {
        return $this->employmentHasOne($this->currentEmploymentExists);
    }

    /** @return MorphMany<Employment, self> */
    public function employments(): MorphMany
    {
        return new MorphMany($this->employmentBuilder($this->employmentExists), new self(), 'employable_type', 'employable_id', 'id');
    }

    /** @return MorphOne<Employment, self> */
    private function employmentHasOne(bool $exists): MorphOne
    {
        return new MorphOne($this->employmentBuilder($exists), new self(), 'employable_type', 'employable_id', 'id');
    }

    /** @return LifecycleStateBuilder<Employment> */
    private function employmentBuilder(bool $exists): LifecycleStateBuilder
    {
        $query = Double::for(QueryBuilder::class);
        $query->expects('exists')->returns($exists);

        return new LifecycleStateBuilder($query, new Employment());
    }
}
