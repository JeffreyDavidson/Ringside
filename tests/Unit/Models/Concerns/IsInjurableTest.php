<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\IsInjurable;
use App\Models\Contracts\Injurable;
use App\Models\Lifecycle\Injury;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use JMac\Testing\Double;

/** @extends EloquentBuilder<Injury> */
final class FakeInjuryBuilder extends EloquentBuilder {}

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
        $builder = $this->injuryBuilder(false);

        return new MorphMany($builder, new self(), 'injurable_type', 'injurable_id', 'id');
    }

    /** @return MorphOne<Injury, self> */
    private function injuryHasOne(bool $exists): MorphOne
    {
        $builder = $this->injuryBuilder($exists);

        return new MorphOne($builder, new self(), 'injurable_type', 'injurable_id', 'id');
    }

    private function injuryBuilder(bool $exists): FakeInjuryBuilder
    {
        $query = Double::for(QueryBuilder::class);
        $query->expects('exists')->returns($exists);
        $builder = new FakeInjuryBuilder($query);
        $builder->setModel(new Injury());

        return $builder;
    }
}

describe('IsInjurable', function () {
    test('provides polymorphic injury relationships', function () {
        $model = new class extends Model implements Injurable
        {
            /** @use IsInjurable<self> */
            use IsInjurable;
        };

        expect($model->injuries())->toBeInstanceOf(MorphMany::class)
            ->and($model->currentInjury())->toBeInstanceOf(MorphOne::class)
            ->and($model->previousInjuries())->toBeInstanceOf(MorphMany::class)
            ->and($model->previousInjury())->toBeInstanceOf(MorphOne::class)
            ->and($model->injuries()->getRelated())->toBeInstanceOf(Injury::class);
    });

    test('checks current injury state', function () {
        $model = new InjuryStateModel();

        expect($model->isInjured())->toBeFalse();

        $model->currentInjuryExists = true;

        expect($model->isInjured())->toBeTrue();
    });

    test('constrains current and previous injury relationships', function () {
        $model = new class extends Model implements Injurable
        {
            /** @use IsInjurable<self> */
            use IsInjurable;
        };

        $currentWheres = $model->currentInjury()->getQuery()->getQuery()->wheres;
        $previousWheres = $model->previousInjuries()->getQuery()->getQuery()->wheres;

        expect(collect($currentWheres)->contains(
            fn (array $where): bool => $where['type'] === 'Null' && $where['column'] === 'ended_at'
        ))->toBeTrue()->and(collect($previousWheres)->contains(
            fn (array $where): bool => $where['type'] === 'NotNull' && $where['column'] === 'ended_at'
        ))->toBeTrue();
    });
});
