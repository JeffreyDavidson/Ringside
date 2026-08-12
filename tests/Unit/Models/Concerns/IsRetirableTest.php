<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\IsRetirable;
use App\Models\Contracts\Retirable;
use App\Models\Lifecycle\Retirement;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use JMac\Testing\Double;

/** @extends EloquentBuilder<Retirement> */
final class FakeRetirementBuilder extends EloquentBuilder {}

/** @implements Retirable<self> */
final class RetirementStateModel extends Model implements Retirable
{
    /** @use IsRetirable<self> */
    use IsRetirable;

    public bool $currentRetirementExists = false;

    public bool $retirementExists = false;

    /** @return MorphOne<Retirement, self> */
    public function currentRetirement(): MorphOne
    {
        return $this->retirementHasOne($this->currentRetirementExists);
    }

    /** @return MorphMany<Retirement, self> */
    public function retirements(): MorphMany
    {
        $builder = $this->retirementBuilder($this->retirementExists);

        return new MorphMany($builder, new self(), 'retirable_type', 'retirable_id', 'id');
    }

    /** @return MorphOne<Retirement, self> */
    private function retirementHasOne(bool $exists): MorphOne
    {
        $builder = $this->retirementBuilder($exists);

        return new MorphOne($builder, new self(), 'retirable_type', 'retirable_id', 'id');
    }

    private function retirementBuilder(bool $exists): FakeRetirementBuilder
    {
        $query = Double::for(QueryBuilder::class);
        $query->expects('exists')->returns($exists);
        $builder = new FakeRetirementBuilder($query);
        $builder->setModel(new Retirement());

        return $builder;
    }
}

describe('IsRetirable', function () {
    test('provides polymorphic retirement relationships', function () {
        $model = new class extends Model implements Retirable
        {
            /** @use IsRetirable<self> */
            use IsRetirable;
        };

        expect($model->retirements())->toBeInstanceOf(MorphMany::class)
            ->and($model->currentRetirement())->toBeInstanceOf(MorphOne::class)
            ->and($model->previousRetirements())->toBeInstanceOf(MorphMany::class)
            ->and($model->previousRetirement())->toBeInstanceOf(MorphOne::class)
            ->and($model->retirements()->getRelated())->toBeInstanceOf(Retirement::class);
    });

    test('checks current and historical retirement state', function () {
        $model = new RetirementStateModel();

        expect($model->isRetired())->toBeFalse()
            ->and($model->hasRetirements())->toBeFalse();

        $model->currentRetirementExists = true;
        $model->retirementExists = true;

        expect($model->isRetired())->toBeTrue()
            ->and($model->hasRetirements())->toBeTrue();
    });

    test('constrains current and previous retirement relationships', function () {
        $model = new class extends Model implements Retirable
        {
            /** @use IsRetirable<self> */
            use IsRetirable;
        };

        $currentWheres = $model->currentRetirement()->getQuery()->getQuery()->wheres;
        $previousWheres = $model->previousRetirements()->getQuery()->getQuery()->wheres;

        expect(collect($currentWheres)->contains(
            fn (array $where): bool => $where['type'] === 'Null' && $where['column'] === 'ended_at'
        ))->toBeTrue()->and(collect($previousWheres)->contains(
            fn (array $where): bool => $where['type'] === 'NotNull' && $where['column'] === 'ended_at'
        ))->toBeTrue();
    });
});
