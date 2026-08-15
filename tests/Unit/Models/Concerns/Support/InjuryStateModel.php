<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns\Support;

use App\Models\Concerns\IsInjurable;
use App\Models\Contracts\Injurable;
use App\Models\Lifecycle\Injury;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use JMac\Testing\Double;

/** @implements Injurable<self> */
final class InjuryStateModel extends Model implements Injurable
{
    /** @use IsInjurable<self> */
    use IsInjurable;

    public bool $currentInjuryExists = false;

    /** @return MorphOne<Injury, self> */
    public function currentInjury(): MorphOne
    {
        return $this->injuryHasOne($this->currentInjuryExists);
    }

    /** @return MorphMany<Injury, self> */
    public function injuries(): MorphMany
    {
        return new MorphMany($this->injuryBuilder(false), new self(), 'injurable_type', 'injurable_id', 'id');
    }

    /** @return MorphOne<Injury, self> */
    private function injuryHasOne(bool $exists): MorphOne
    {
        return new MorphOne($this->injuryBuilder($exists), new self(), 'injurable_type', 'injurable_id', 'id');
    }

    /** @return LifecycleStateBuilder<Injury> */
    private function injuryBuilder(bool $exists): LifecycleStateBuilder
    {
        $query = Double::for(QueryBuilder::class);
        $query->expects('exists')->returns($exists);

        return new LifecycleStateBuilder($query, new Injury());
    }
}
