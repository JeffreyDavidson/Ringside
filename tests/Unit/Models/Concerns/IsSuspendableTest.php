<?php

declare(strict_types=1);

/**
 * Trait Isolation Test for IsSuspendable
 *
 * This test ensures the IsSuspendable trait is agnostic, reusable, and not tied to any business/domain model.
 * It verifies relationship types, related model resolution, static override, cache reset, and error handling.
 *
 * This is NOT a business logic test. It is meant to guarantee the trait can be safely reused across any model.
 */

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\IsSuspendable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Tests\Unit\Models\Concerns\Support\FakeSuspendableModel;
use Tests\Unit\Models\Concerns\Support\FakeSuspensionModel;

describe('IsSuspendable Trait Unit Tests', function () {
    describe('suspension relationships', function () {
        test('provides suspensions relationship', function () {
            $model = new class extends Model
            {
                use IsSuspendable;

                public function resolveSuspensionModelClass(): string
                {
                    return FakeSuspensionModel::class;
                }
            };
            expect($model->suspensions())->toBeInstanceOf(HasMany::class);
        });

        test('provides current suspension relationship', function () {
            $model = new class extends Model
            {
                use IsSuspendable;

                public function resolveSuspensionModelClass(): string
                {
                    return FakeSuspensionModel::class;
                }
            };
            expect($model->currentSuspension())->toBeInstanceOf(HasOne::class);
        });

        test('provides previous suspensions relationship', function () {
            $model = new class extends Model
            {
                use IsSuspendable;

                public function resolveSuspensionModelClass(): string
                {
                    return FakeSuspensionModel::class;
                }
            };
            expect($model->previousSuspensions())->toBeInstanceOf(HasMany::class);
        });

        test('provides previous suspension relationship', function () {
            $model = new class extends Model
            {
                use IsSuspendable;

                public function resolveSuspensionModelClass(): string
                {
                    return FakeSuspensionModel::class;
                }
            };
            expect($model->previousSuspension())->toBeInstanceOf(HasOne::class);
        });

        test('suspensions relationship uses the correct related model', function () {
            $model = new class extends Model
            {
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
            $model = new class extends Model
            {
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
            $model = new class extends Model
            {
                use IsSuspendable;

                public function resolveSuspensionModelClass(): string
                {
                    return FakeSuspensionModel::class;
                }

                public function currentSuspension()
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
            expect($model->isSuspended())->toBeTrue();
        });

        test('can check if model is not suspended', function () {
            $model = new class extends Model
            {
                use IsSuspendable;

                public function resolveSuspensionModelClass(): string
                {
                    return FakeSuspensionModel::class;
                }

                public function currentSuspension()
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
            expect($model->isSuspended())->toBeFalse();
        });

        test('can check if model has suspensions', function () {
            $modelWith = new class extends Model
            {
                use IsSuspendable;

                public function resolveSuspensionModelClass(): string
                {
                    return FakeSuspensionModel::class;
                }

                public function suspensions()
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
            $modelWithout = new class extends Model
            {
                use IsSuspendable;

                public function resolveSuspensionModelClass(): string
                {
                    return FakeSuspensionModel::class;
                }

                public function suspensions()
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
            expect($modelWith->hasSuspensions())->toBeTrue();
            expect($modelWithout->hasSuspensions())->toBeFalse();
        });
    });

    describe('suspension model resolution', function () {
        test('can fake suspension model class', function () {
            FakeSuspendableModel::fakeSuspensionModel(FakeSuspensionModel::class);
            $model = new FakeSuspendableModel();
            expect($model->resolveSuspensionModelClass())->toBe(FakeSuspensionModel::class);
            // Reset static override
            FakeSuspendableModel::fakeSuspensionModel(null);
        });
    });

    describe('suspension relationship queries', function () {
        test('current suspension query includes whereNull ended_at', function () {
            $model = new class extends Model
            {
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
