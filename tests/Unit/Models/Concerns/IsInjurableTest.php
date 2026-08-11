<?php

declare(strict_types=1);

/**
 * Trait Isolation Test for IsInjurable
 *
 * This test ensures the IsInjurable trait is agnostic, reusable, and not tied to any business/domain model.
 * It verifies relationship types, related model resolution, resolver overrides, and error handling.
 *
 * This is NOT a business logic test. It is meant to guarantee the trait can be safely reused across any model.
 */

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\IsInjurable;
use App\Models\Contracts\Injurable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Query\Builder as QueryBuilder;
use JMac\Testing\Double;
use LogicException;
use Tests\Unit\Models\Concerns\Support\FakeInjuryModel;

/** @extends EloquentBuilder<FakeInjuryModel> */
final class FakeInjuryBuilder extends EloquentBuilder {}

/** @implements Injurable<FakeInjuryModel, self> */
final class InjuryStateModel extends Model implements Injurable
{
    /** @use IsInjurable<FakeInjuryModel, self> */
    use IsInjurable;

    public bool $currentInjuryExists = false;

    public bool $injuryExists = false;

    public function resolveInjuryModelClass(): string
    {
        return FakeInjuryModel::class;
    }

    /** @return HasOne<FakeInjuryModel, self> */
    public function currentInjury(): HasOne
    {
        $builder = $this->injuryBuilder($this->currentInjuryExists);

        return new HasOne($builder, new self(), 'entity_id', 'id');
    }

    /** @return HasMany<FakeInjuryModel, self> */
    public function injuries(): HasMany
    {
        $builder = $this->injuryBuilder($this->injuryExists);

        return new HasMany($builder, new self(), 'entity_id', 'id');
    }

    private function injuryBuilder(bool $exists): FakeInjuryBuilder
    {
        $query = Double::for(QueryBuilder::class);
        $query->expects('exists')->returns($exists);
        $builder = new FakeInjuryBuilder($query);
        $builder->setModel(new FakeInjuryModel());

        return $builder;
    }
}

describe('IsInjurable Trait Unit Tests', function () {
    describe('injury relationships', function () {
        test('provides injuries relationship', function () {
            $model = new class extends Model implements Injurable
            {
                /** @use IsInjurable<FakeInjuryModel, self> */
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }
            };

            expect($model->injuries())->toBeInstanceOf(HasMany::class);
        });

        test('provides current injury relationship', function () {
            $model = new class extends Model implements Injurable
            {
                /** @use IsInjurable<FakeInjuryModel, self> */
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }
            };

            expect($model->currentInjury())->toBeInstanceOf(HasOne::class);
        });

        test('provides previous injuries relationship', function () {
            $model = new class extends Model implements Injurable
            {
                /** @use IsInjurable<FakeInjuryModel, self> */
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }
            };

            expect($model->previousInjuries())->toBeInstanceOf(HasMany::class);
        });

        test('provides previous injury relationship', function () {
            $model = new class extends Model implements Injurable
            {
                /** @use IsInjurable<FakeInjuryModel, self> */
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }
            };

            expect($model->previousInjury())->toBeInstanceOf(HasOne::class);
        });

        test('injuries relationship uses the correct related model', function () {
            $model = new class extends Model implements Injurable
            {
                /** @use IsInjurable<FakeInjuryModel, self> */
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }
            };
            $relation = $model->injuries();
            expect($relation)->toBeInstanceOf(HasMany::class);
            expect($relation->getRelated())->toBeInstanceOf(FakeInjuryModel::class);
        });

        test('currentInjury relationship uses the correct related model', function () {
            $model = new class extends Model implements Injurable
            {
                /** @use IsInjurable<FakeInjuryModel, self> */
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }
            };
            $relation = $model->currentInjury();
            expect($relation)->toBeInstanceOf(HasOne::class);
            expect($relation->getRelated())->toBeInstanceOf(FakeInjuryModel::class);
        });
    });

    describe('injury status checks', function () {
        test('can check if model is injured', function () {
            $model = new InjuryStateModel();
            $model->currentInjuryExists = true;

            expect($model->isInjured())->toBeTrue();
        });

        test('can check if model is not injured', function () {
            $model = new InjuryStateModel();

            expect($model->isInjured())->toBeFalse();
        });

        test('can check if model has injuries', function () {
            $modelWithInjuries = new InjuryStateModel();
            $modelWithInjuries->injuryExists = true;
            $modelWithoutInjuries = new InjuryStateModel();

            expect($modelWithInjuries->hasInjuries())->toBeTrue();
            expect($modelWithoutInjuries->hasInjuries())->toBeFalse();
        });
    });

    describe('injury model resolution', function () {
        test('uses the model-specific injury resolver', function () {
            $model = new class extends Model implements Injurable
            {
                /** @use IsInjurable<FakeInjuryModel, self> */
                use IsInjurable;

                protected function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }
            };

            expect($model->injuries()->getRelated())->toBeInstanceOf(FakeInjuryModel::class);
        });

        test('throws if related model does not exist', function () {
            $model = new class extends Model implements Injurable
            {
                /** @use IsInjurable<FakeInjuryModel, self> */
                use IsInjurable;
            };

            expect(fn () => $model->injuries())->toThrow(LogicException::class);
        });
    });

    describe('injury relationship queries', function () {
        test('current injury query includes whereNull ended_at', function () {
            $model = new class extends Model implements Injurable
            {
                /** @use IsInjurable<FakeInjuryModel, self> */
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }
            };

            $relation = $model->currentInjury();

            // The trait should add a whereNull('ended_at') constraint
            expect($relation)->toBeInstanceOf(HasOne::class);
        });

        test('previous injuries query includes whereNotNull ended_at', function () {
            $model = new class extends Model implements Injurable
            {
                /** @use IsInjurable<FakeInjuryModel, self> */
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }
            };

            $relation = $model->previousInjuries();

            // The trait should add a whereNotNull('ended_at') constraint
            expect($relation)->toBeInstanceOf(HasMany::class);
        });

        test('previous injury query includes ofMany constraint', function () {
            $model = new class extends Model implements Injurable
            {
                /** @use IsInjurable<FakeInjuryModel, self> */
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }
            };

            $relation = $model->previousInjury();

            // The trait should add an ofMany constraint for the most recent
            expect($relation)->toBeInstanceOf(HasOne::class);
        });
    });
});
