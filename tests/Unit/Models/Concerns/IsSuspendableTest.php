<?php

declare(strict_types=1);

/**
 * Trait Isolation Test for IsSuspendable
 *
 * This test ensures the IsSuspendable trait is agnostic, reusable, and not tied to any business/domain model.
 * It verifies relationship types, related model resolution, resolver overrides, and error handling.
 *
 * This is NOT a business logic test. It is meant to guarantee the trait can be safely reused across any model.
 */

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\IsSuspendable;
use App\Models\Contracts\Suspendable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use JMac\Testing\Double;
use Tests\Unit\Models\Concerns\Support\FakeSuspendableModel;
use Tests\Unit\Models\Concerns\Support\FakeSuspensionModel;

/** @extends EloquentBuilder<FakeSuspensionModel> */
final class FakeSuspensionBuilder extends EloquentBuilder {}

/** @implements Suspendable<FakeSuspensionModel, self> */
final class SuspensionStateModel extends Model implements Suspendable
{
    /** @use IsSuspendable<FakeSuspensionModel, self> */
    use IsSuspendable;

    public bool $currentSuspensionExists = false;

    public bool $suspensionExists = false;

    public function resolveSuspensionModelClass(): string
    {
        return FakeSuspensionModel::class;
    }

    /** @return HasOne<FakeSuspensionModel, self> */
    public function currentSuspension(): HasOne
    {
        $builder = $this->suspensionBuilder($this->currentSuspensionExists);

        return new HasOne($builder, new self(), 'entity_id', 'id');
    }

    /** @return HasMany<FakeSuspensionModel, self> */
    public function suspensions(): HasMany
    {
        $builder = $this->suspensionBuilder($this->suspensionExists);

        return new HasMany($builder, new self(), 'entity_id', 'id');
    }

    private function suspensionBuilder(bool $exists): FakeSuspensionBuilder
    {
        $query = Double::for(QueryBuilder::class);
        $query->expects('exists')->returns($exists);
        $builder = new FakeSuspensionBuilder($query);
        $builder->setModel(new FakeSuspensionModel());

        return $builder;
    }
}

describe('IsSuspendable Trait Unit Tests', function () {
    describe('suspension relationships', function () {
        test('provides suspensions relationship', function () {
            $model = new class extends Model implements Suspendable
            {
                /** @use IsSuspendable<FakeSuspensionModel, self> */
                use IsSuspendable;

                public function resolveSuspensionModelClass(): string
                {
                    return FakeSuspensionModel::class;
                }
            };
            expect($model->suspensions())->toBeInstanceOf(HasMany::class);
        });

        test('provides current suspension relationship', function () {
            $model = new class extends Model implements Suspendable
            {
                /** @use IsSuspendable<FakeSuspensionModel, self> */
                use IsSuspendable;

                public function resolveSuspensionModelClass(): string
                {
                    return FakeSuspensionModel::class;
                }
            };
            expect($model->currentSuspension())->toBeInstanceOf(HasOne::class);
        });

        test('provides previous suspensions relationship', function () {
            $model = new class extends Model implements Suspendable
            {
                /** @use IsSuspendable<FakeSuspensionModel, self> */
                use IsSuspendable;

                public function resolveSuspensionModelClass(): string
                {
                    return FakeSuspensionModel::class;
                }
            };
            expect($model->previousSuspensions())->toBeInstanceOf(HasMany::class);
        });

        test('provides previous suspension relationship', function () {
            $model = new class extends Model implements Suspendable
            {
                /** @use IsSuspendable<FakeSuspensionModel, self> */
                use IsSuspendable;

                public function resolveSuspensionModelClass(): string
                {
                    return FakeSuspensionModel::class;
                }
            };
            expect($model->previousSuspension())->toBeInstanceOf(HasOne::class);
        });

        test('suspensions relationship uses the correct related model', function () {
            $model = new class extends Model implements Suspendable
            {
                /** @use IsSuspendable<FakeSuspensionModel, self> */
                use IsSuspendable;

                public function resolveSuspensionModelClass(): string
                {
                    return FakeSuspensionModel::class;
                }
            };
            $relation = $model->suspensions();
            expect($relation)->toBeInstanceOf(HasMany::class);
            expect($relation->getRelated())->toBeInstanceOf(FakeSuspensionModel::class);
        });

        test('currentSuspension relationship uses the correct related model', function () {
            $model = new class extends Model implements Suspendable
            {
                /** @use IsSuspendable<FakeSuspensionModel, self> */
                use IsSuspendable;

                public function resolveSuspensionModelClass(): string
                {
                    return FakeSuspensionModel::class;
                }
            };
            $relation = $model->currentSuspension();
            expect($relation)->toBeInstanceOf(HasOne::class);
            expect($relation->getRelated())->toBeInstanceOf(FakeSuspensionModel::class);
        });
    });

    describe('suspension status checks', function () {
        test('can check if model is suspended', function () {
            $model = new SuspensionStateModel();
            $model->currentSuspensionExists = true;

            expect($model->isSuspended())->toBeTrue();
        });

        test('can check if model is not suspended', function () {
            $model = new SuspensionStateModel();

            expect($model->isSuspended())->toBeFalse();
        });

        test('can check if model has suspensions', function () {
            $modelWith = new SuspensionStateModel();
            $modelWith->suspensionExists = true;
            $modelWithout = new SuspensionStateModel();

            expect($modelWith->hasSuspensions())->toBeTrue();
            expect($modelWithout->hasSuspensions())->toBeFalse();
        });
    });

    describe('suspension model resolution', function () {
        test('uses the model-specific suspension resolver', function () {
            $model = new FakeSuspendableModel();

            expect($model->suspensions()->getRelated())->toBeInstanceOf(FakeSuspensionModel::class);
        });
    });

    describe('suspension relationship queries', function () {
        test('current suspension query includes whereNull ended_at', function () {
            $model = new class extends Model implements Suspendable
            {
                /** @use IsSuspendable<FakeSuspensionModel, self> */
                use IsSuspendable;

                public function resolveSuspensionModelClass(): string
                {
                    return FakeSuspensionModel::class;
                }
            };
            $relation = $model->currentSuspension();
            $wheres = $relation->getQuery()->getQuery()->wheres;
            $hasWhereNull = collect($wheres)->contains(function ($where) {
                return ($where['type'] ?? null) === 'Null' && ($where['column'] ?? null) === 'ended_at';
            });
            expect($hasWhereNull)->toBeTrue();
        });
    });
});
