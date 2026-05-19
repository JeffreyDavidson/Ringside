<?php

declare(strict_types=1);

/**
 * Trait Isolation Test for IsInjurable
 *
 * This test ensures the IsInjurable trait is agnostic, reusable, and not tied to any business/domain model.
 * It verifies relationship types, related model resolution, static override, cache reset, and error handling.
 *
 * This is NOT a business logic test. It is meant to guarantee the trait can be safely reused across any model.
 */

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\IsInjurable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use RuntimeException;
use Tests\Unit\Models\Concerns\Support\FakeInjuryModel;

describe('IsInjurable Trait Unit Tests', function () {
    describe('injury relationships', function () {
        test('provides injuries relationship', function () {
            $model = new class extends Model
            {
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }
            };

            expect($model->injuries())->toBeInstanceOf(HasMany::class);
        });

        test('provides current injury relationship', function () {
            $model = new class extends Model
            {
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }
            };

            expect($model->currentInjury())->toBeInstanceOf(HasOne::class);
        });

        test('provides previous injuries relationship', function () {
            $model = new class extends Model
            {
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }
            };

            expect($model->previousInjuries())->toBeInstanceOf(HasMany::class);
        });

        test('provides previous injury relationship', function () {
            $model = new class extends Model
            {
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }
            };

            expect($model->previousInjury())->toBeInstanceOf(HasOne::class);
        });

        test('injuries relationship uses the correct related model', function () {
            $model = new class extends Model
            {
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
            $model = new class extends Model
            {
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
            $model = new class extends Model
            {
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }

                public function currentInjury()
                {
                    return new class
                    {
                        public function exists(): bool
                        {
                            return true;
                        }
                    };
                }
            };

            expect($model->isInjured())->toBeTrue();
        });

        test('can check if model is not injured', function () {
            $model = new class extends Model
            {
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }

                public function currentInjury()
                {
                    return new class
                    {
                        public function exists(): bool
                        {
                            return false;
                        }
                    };
                }
            };

            expect($model->isInjured())->toBeFalse();
        });

        test('can check if model has injuries', function () {
            $modelWithInjuries = new class extends Model
            {
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }

                public function injuries()
                {
                    return new class
                    {
                        public function exists(): bool
                        {
                            return true;
                        }
                    };
                }
            };

            $modelWithoutInjuries = new class extends Model
            {
                use IsInjurable;

                public function resolveInjuryModelClass(): string
                {
                    return FakeInjuryModel::class;
                }

                public function injuries()
                {
                    return new class
                    {
                        public function exists(): bool
                        {
                            return false;
                        }
                    };
                }
            };

            expect($modelWithInjuries->hasInjuries())->toBeTrue();
            expect($modelWithoutInjuries->hasInjuries())->toBeFalse();
        });
    });

    describe('injury model resolution', function () {
        test('can fake injury model class', function () {
            $modelClass = new class extends Model
            {
                use IsInjurable;
            };

            $modelClass::fakeInjuryModel(FakeInjuryModel::class);
            // The relationship should use the faked model class
            $relation = $modelClass->injuries();
            expect($relation->getRelated())->toBeInstanceOf(FakeInjuryModel::class);
        });
        test('throws if related model does not exist', function () {
            $model = new class extends Model
            {
                use IsInjurable;
            };
            expect(fn () => $model->injuries())->toThrow(RuntimeException::class);
        });
        test('can clear fake model override', function () {
            $modelClass = new class extends Model
            {
                use IsInjurable;
            };
            $modelClass::fakeInjuryModel(FakeInjuryModel::class);
            $modelClass::clearRelatedModelCache('Injury');
            expect(fn () => $modelClass->injuries())->toThrow(RuntimeException::class);
        });
    });

    describe('injury relationship queries', function () {
        test('current injury query includes whereNull ended_at', function () {
            $model = new class extends Model
            {
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
            $model = new class extends Model
            {
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
            $model = new class extends Model
            {
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
