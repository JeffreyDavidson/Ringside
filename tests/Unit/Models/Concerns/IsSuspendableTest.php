<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\IsSuspendable;
use App\Models\Contracts\Suspendable;
use App\Models\Lifecycle\Suspension;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use JMac\Testing\Double;

/** @extends EloquentBuilder<Suspension> */
final class FakeSuspensionBuilder extends EloquentBuilder {}

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
        $builder = $this->suspensionBuilder(false);

        return new MorphMany($builder, new self(), 'suspendable_type', 'suspendable_id', 'id');
    }

    /** @return MorphOne<Suspension, self> */
    private function suspensionHasOne(bool $exists): MorphOne
    {
        $builder = $this->suspensionBuilder($exists);

        return new MorphOne($builder, new self(), 'suspendable_type', 'suspendable_id', 'id');
    }

    private function suspensionBuilder(bool $exists): FakeSuspensionBuilder
    {
        $query = Double::for(QueryBuilder::class);
        $query->expects('exists')->returns($exists);
        $builder = new FakeSuspensionBuilder($query);
        $builder->setModel(new Suspension());

        return $builder;
    }
}

describe('IsSuspendable', function () {
    test('provides polymorphic suspension relationships', function () {
        $model = new class extends Model implements Suspendable
        {
            /** @use IsSuspendable<self> */
            use IsSuspendable;
        };

        expect($model->suspensions())->toBeInstanceOf(MorphMany::class)
            ->and($model->currentSuspension())->toBeInstanceOf(MorphOne::class)
            ->and($model->previousSuspensions())->toBeInstanceOf(MorphMany::class)
            ->and($model->previousSuspension())->toBeInstanceOf(MorphOne::class)
            ->and($model->suspensions()->getRelated())->toBeInstanceOf(Suspension::class);
    });

    test('checks current suspension state', function () {
        $model = new SuspensionStateModel();

        expect($model->isSuspended())->toBeFalse();

        $model->currentSuspensionExists = true;

        expect($model->isSuspended())->toBeTrue();
    });

    test('constrains current and previous suspension relationships', function () {
        $model = new class extends Model implements Suspendable
        {
            /** @use IsSuspendable<self> */
            use IsSuspendable;
        };

        $currentWheres = $model->currentSuspension()->getQuery()->getQuery()->wheres;
        $previousWheres = $model->previousSuspensions()->getQuery()->getQuery()->wheres;

        expect(collect($currentWheres)->contains(
            fn (array $where): bool => $where['type'] === 'Null' && $where['column'] === 'ended_at'
        ))->toBeTrue()->and(collect($previousWheres)->contains(
            fn (array $where): bool => $where['type'] === 'NotNull' && $where['column'] === 'ended_at'
        ))->toBeTrue();
    });
});
