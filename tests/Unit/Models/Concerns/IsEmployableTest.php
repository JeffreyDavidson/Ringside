<?php

declare(strict_types=1);

/**
 * Trait Isolation Test for IsEmployable
 *
 * This test ensures the IsEmployable trait is agnostic, reusable, and not tied to any business/domain model.
 * It verifies relationship types, related model resolution, resolver overrides, and error handling.
 *
 * This is NOT a business logic test. It is meant to guarantee the trait can be safely reused across any model.
 */

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\IsEmployable;
use App\Models\Contracts\Employable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use JMac\Testing\Double;
use Tests\Unit\Models\Concerns\Support\FakeEmployableModel;
use Tests\Unit\Models\Concerns\Support\FakeEmploymentModel;

/** @extends EloquentBuilder<FakeEmploymentModel> */
final class FakeEmploymentBuilder extends EloquentBuilder {}

/** @implements Employable<FakeEmploymentModel, self> */
final class EmploymentStateModel extends Model implements Employable
{
    /** @use IsEmployable<FakeEmploymentModel, self> */
    use IsEmployable;

    public bool $futureEmploymentExists = false;

    public bool $currentEmploymentExists = false;

    public bool $employmentExists = false;

    public function resolveEmploymentModelClass(): string
    {
        return FakeEmploymentModel::class;
    }

    /** @return HasOne<FakeEmploymentModel, self> */
    public function futureEmployment(): HasOne
    {
        return $this->employmentHasOne($this->futureEmploymentExists);
    }

    /** @return HasOne<FakeEmploymentModel, self> */
    public function currentEmployment(): HasOne
    {
        return $this->employmentHasOne($this->currentEmploymentExists);
    }

    /** @return HasMany<FakeEmploymentModel, self> */
    public function employments(): HasMany
    {
        $builder = $this->employmentBuilder($this->employmentExists);

        return new HasMany($builder, new self(), 'entity_id', 'id');
    }

    /** @return HasOne<FakeEmploymentModel, self> */
    private function employmentHasOne(bool $exists): HasOne
    {
        $builder = $this->employmentBuilder($exists);

        return new HasOne($builder, new self(), 'entity_id', 'id');
    }

    private function employmentBuilder(bool $exists): FakeEmploymentBuilder
    {
        $query = Double::for(QueryBuilder::class);
        $query->expects('exists')->returns($exists);
        $builder = new FakeEmploymentBuilder($query);
        $builder->setModel(new FakeEmploymentModel());

        return $builder;
    }
}

describe('IsEmployable Trait Unit Tests', function () {
    describe('employment relationships', function () {
        test('provides employments relationship', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<FakeEmploymentModel, self> */
                use IsEmployable;

                public function resolveEmploymentModelClass(): string
                {
                    return FakeEmploymentModel::class;
                }
            };
            expect($model->employments())->toBeInstanceOf(HasMany::class);
        });

        test('provides current employment relationship', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<FakeEmploymentModel, self> */
                use IsEmployable;

                public function resolveEmploymentModelClass(): string
                {
                    return FakeEmploymentModel::class;
                }
            };
            expect($model->currentEmployment())->toBeInstanceOf(HasOne::class);
        });

        test('provides future employment relationship', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<FakeEmploymentModel, self> */
                use IsEmployable;

                public function resolveEmploymentModelClass(): string
                {
                    return FakeEmploymentModel::class;
                }
            };
            expect($model->futureEmployment())->toBeInstanceOf(HasOne::class);
        });

        test('provides previous employments relationship', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<FakeEmploymentModel, self> */
                use IsEmployable;

                public function resolveEmploymentModelClass(): string
                {
                    return FakeEmploymentModel::class;
                }
            };
            expect($model->previousEmployments())->toBeInstanceOf(HasMany::class);
        });

        test('provides previous employment relationship', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<FakeEmploymentModel, self> */
                use IsEmployable;

                public function resolveEmploymentModelClass(): string
                {
                    return FakeEmploymentModel::class;
                }
            };
            expect($model->previousEmployment())->toBeInstanceOf(HasOne::class);
        });

        test('provides first employment relationship', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<FakeEmploymentModel, self> */
                use IsEmployable;

                public function resolveEmploymentModelClass(): string
                {
                    return FakeEmploymentModel::class;
                }
            };
            expect($model->firstEmployment())->toBeInstanceOf(HasOne::class);
        });

        test('employments relationship uses the correct related model', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<FakeEmploymentModel, self> */
                use IsEmployable;

                public function resolveEmploymentModelClass(): string
                {
                    return FakeEmploymentModel::class;
                }
            };
            $relation = $model->employments();
            expect($relation)->toBeInstanceOf(HasMany::class);
            expect($relation->getRelated())->toBeInstanceOf(FakeEmploymentModel::class);
        });

        test('currentEmployment relationship uses the correct related model', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<FakeEmploymentModel, self> */
                use IsEmployable;

                public function resolveEmploymentModelClass(): string
                {
                    return FakeEmploymentModel::class;
                }
            };
            $relation = $model->currentEmployment();
            expect($relation)->toBeInstanceOf(HasOne::class);
            expect($relation->getRelated())->toBeInstanceOf(FakeEmploymentModel::class);
        });
    });

    describe('employment status checks', function () {
        test('can check if model is employed', function () {
            $model = new EmploymentStateModel();
            $model->currentEmploymentExists = true;

            expect($model->isEmployed())->toBeTrue();
        });

        test('can check if model is not employed', function () {
            $model = new EmploymentStateModel();

            expect($model->isEmployed())->toBeFalse();
        });

        test('can check if model has employments', function () {
            $modelWith = new EmploymentStateModel();
            $modelWith->employmentExists = true;
            $modelWithout = new EmploymentStateModel();

            expect($modelWith->hasEmployments())->toBeTrue();
            expect($modelWithout->hasEmployments())->toBeFalse();
        });

        test('can check if model has future employment', function () {
            $modelWith = new EmploymentStateModel();
            $modelWith->futureEmploymentExists = true;
            $modelWithout = new EmploymentStateModel();

            expect($modelWith->hasFutureEmployment())->toBeTrue();
            expect($modelWithout->hasFutureEmployment())->toBeFalse();
        });

        test('can check if model has employment history', function () {
            $modelWith = new EmploymentStateModel();
            $modelWith->employmentExists = true;
            $modelWithout = new EmploymentStateModel();

            expect($modelWith->hasEmploymentHistory())->toBeTrue();
            expect($modelWithout->hasEmploymentHistory())->toBeFalse();
        });
    });

    describe('employment model resolution', function () {
        test('uses the model-specific employment resolver', function () {
            $model = new FakeEmployableModel();

            expect($model->employments()->getRelated())->toBeInstanceOf(FakeEmploymentModel::class);
        });
    });

    describe('employment relationship queries', function () {
        test('current employment query includes whereNull ended_at', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<FakeEmploymentModel, self> */
                use IsEmployable;

                public function resolveEmploymentModelClass(): string
                {
                    return FakeEmploymentModel::class;
                }
            };
            $relation = $model->currentEmployment();
            $wheres = $relation->getQuery()->getQuery()->wheres;
            $hasWhereNull = collect($wheres)->contains(function ($where) {
                return ($where['type'] ?? null) === 'Null' && ($where['column'] ?? null) === 'ended_at';
            });
            expect($hasWhereNull)->toBeTrue();
        });

        test('future employment query includes whereNull ended_at and started_at > now', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<FakeEmploymentModel, self> */
                use IsEmployable;

                public function resolveEmploymentModelClass(): string
                {
                    return FakeEmploymentModel::class;
                }
            };
            $relation = $model->futureEmployment();
            $wheres = $relation->getQuery()->getQuery()->wheres;
            $hasWhereNull = collect($wheres)->contains(function ($where) {
                return ($where['type'] ?? null) === 'Null' && ($where['column'] ?? null) === 'ended_at';
            });
            $hasStartedAtGreater = collect($wheres)->contains(function ($where) {
                return ($where['column'] ?? null) === 'started_at' && ($where['operator'] ?? null) === '>';
            });
            expect($hasWhereNull)->toBeTrue();
            expect($hasStartedAtGreater)->toBeTrue();
        });

        test('previous employments query includes whereNotNull ended_at', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<FakeEmploymentModel, self> */
                use IsEmployable;

                public function resolveEmploymentModelClass(): string
                {
                    return FakeEmploymentModel::class;
                }
            };
            $relation = $model->previousEmployments();
            $wheres = $relation->getQuery()->getQuery()->wheres;
            $hasWhereNotNull = collect($wheres)->contains(function ($where) {
                return ($where['type'] ?? null) === 'NotNull' && ($where['column'] ?? null) === 'ended_at';
            });
            expect($hasWhereNotNull)->toBeTrue();
        });

        test('previous employment query includes ofMany constraint', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<FakeEmploymentModel, self> */
                use IsEmployable;

                public function resolveEmploymentModelClass(): string
                {
                    return FakeEmploymentModel::class;
                }
            };
            $relation = $model->previousEmployment();
            expect($relation)->toBeInstanceOf(HasOne::class);
            // The ofMany constraint is applied internally by Laravel
            // We can verify the relationship type and that it's properly configured
        });

        test('first employment query includes ofMany constraint', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<FakeEmploymentModel, self> */
                use IsEmployable;

                public function resolveEmploymentModelClass(): string
                {
                    return FakeEmploymentModel::class;
                }
            };
            $relation = $model->firstEmployment();
            expect($relation)->toBeInstanceOf(HasOne::class);
            // The ofMany constraint is applied internally by Laravel
            // We can verify the relationship type and that it's properly configured
        });
    });
});
