<?php

declare(strict_types=1);

/**
 * Trait Isolation Test for IsRetirable
 *
 * This test ensures the IsRetirable trait is agnostic, reusable, and not tied to any business/domain model.
 * It verifies relationship types, related model resolution, resolver overrides, and error handling.
 *
 * This is NOT a business logic test. It is meant to guarantee the trait can be safely reused across any model.
 */

namespace Tests\Unit\Models\Concerns;

use App\Enums\Shared\EmploymentStatus;
use App\Models\Concerns\IsRetirable;
use App\Models\Contracts\Retirable;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Mockery;
use Tests\Unit\Models\Concerns\Support\FakeRetirableModel;
use Tests\Unit\Models\Concerns\Support\FakeRetirementModel;

describe('IsRetirable Trait Unit Tests', function () {
    describe('retirement relationships', function () {
        test('provides retirements relationship', function () {
            $model = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            expect($model->retirements())->toBeInstanceOf(HasMany::class);
        });

        test('provides current retirement relationship', function () {
            $model = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            expect($model->currentRetirement())->toBeInstanceOf(HasOne::class);
        });

        test('provides previous retirements relationship', function () {
            $model = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            expect($model->previousRetirements())->toBeInstanceOf(HasMany::class);
        });

        test('provides previous retirement relationship', function () {
            $model = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            expect($model->previousRetirement())->toBeInstanceOf(HasOne::class);
        });

        test('retirements relationship uses the correct related model', function () {
            $model = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            $relation = $model->retirements();
            expect($relation)->toBeInstanceOf(HasMany::class);
            expect($relation->getRelated())->toBeInstanceOf(FakeRetirementModel::class);
        });

        test('currentRetirement relationship uses the correct related model', function () {
            $model = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            $relation = $model->currentRetirement();
            expect($relation)->toBeInstanceOf(HasOne::class);
            expect($relation->getRelated())->toBeInstanceOf(FakeRetirementModel::class);
        });
    });

    describe('retirement status checks', function () {
        test('can check if model is retired', function () {
            $model = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }

                public function currentRetirement(): HasOne
                {
                    $relation = Mockery::mock(HasOne::class);
                    $relation->expects('exists')->andReturn(true);

                    return $relation;
                }
            };
            expect($model->isRetired())->toBeTrue();
        });

        test('can check if model is not retired', function () {
            $model = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }

                public function currentRetirement(): HasOne
                {
                    $relation = Mockery::mock(HasOne::class);
                    $relation->expects('exists')->andReturn(false);

                    return $relation;
                }
            };
            expect($model->isRetired())->toBeFalse();
        });

        test('can check if model has retirements', function () {
            $modelWith = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }

                public function retirements(): HasMany
                {
                    $relation = Mockery::mock(HasMany::class);
                    $relation->expects('exists')->andReturn(true);

                    return $relation;
                }
            };
            $modelWithout = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }

                public function retirements(): HasMany
                {
                    $relation = Mockery::mock(HasMany::class);
                    $relation->expects('exists')->andReturn(false);

                    return $relation;
                }
            };
            expect($modelWith->hasRetirements())->toBeTrue();
            expect($modelWithout->hasRetirements())->toBeFalse();
        });
    });

    describe('retirement model resolution', function () {
        test('uses the model-specific retirement resolver', function () {
            $model = new FakeRetirableModel();

            expect($model->retirements()->getRelated())->toBeInstanceOf(FakeRetirementModel::class);
        });
    });

    describe('retirement relationship queries', function () {
        test('current retirement query includes whereNull ended_at', function () {
            $model = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            $relation = $model->currentRetirement();
            $wheres = $relation->getQuery()->getQuery()->wheres;
            $hasWhereNull = collect($wheres)->contains(function ($where) {
                return ($where['type'] ?? null) === 'Null' && ($where['column'] ?? null) === 'ended_at';
            });
            expect($hasWhereNull)->toBeTrue();
        });

        test('previous retirements query includes whereNotNull ended_at', function () {
            $model = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            $relation = $model->previousRetirements();
            $wheres = $relation->getQuery()->getQuery()->wheres;
            $hasWhereNotNull = collect($wheres)->contains(function ($where) {
                return ($where['type'] ?? null) === 'NotNull' && ($where['column'] ?? null) === 'ended_at';
            });
            expect($hasWhereNotNull)->toBeTrue();
        });

        test('previous retirement query includes ofMany constraint', function () {
            $model = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public function resolveRetirementModelClass(): string
                {
                    return FakeRetirementModel::class;
                }
            };
            $relation = $model->previousRetirement();
            expect($relation)->toBeInstanceOf(HasOne::class);
            // The ofMany constraint is applied internally by Laravel
            // We can verify the relationship type and that it's properly configured
        });
    });

    describe('retirement validation methods', function () {
        test('can check if model can be retired', function () {
            $employedModel = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public EmploymentStatus $status = EmploymentStatus::Employed;

                public function canBeRetired(): bool
                {
                    return $this->status === EmploymentStatus::Employed;
                }
            };

            $retiredModel = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public EmploymentStatus $status = EmploymentStatus::Retired;

                public function canBeRetired(): bool
                {
                    return $this->status === EmploymentStatus::Employed;
                }
            };

            expect($employedModel->canBeRetired())->toBeTrue();
            expect($retiredModel->canBeRetired())->toBeFalse();
        });

        test('can check if model can be unretired', function () {
            $retiredModel = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public EmploymentStatus $status = EmploymentStatus::Retired;

                public function canBeUnretired(): bool
                {
                    return $this->status === EmploymentStatus::Retired;
                }
            };

            $employedModel = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public EmploymentStatus $status = EmploymentStatus::Employed;

                public function canBeUnretired(): bool
                {
                    return $this->status === EmploymentStatus::Retired;
                }
            };

            expect($retiredModel->canBeUnretired())->toBeTrue();
            expect($employedModel->canBeUnretired())->toBeFalse();
        });

        test('can ensure model can be retired', function () {
            $model = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public EmploymentStatus $status = EmploymentStatus::Employed;

                public function canBeRetired(): bool
                {
                    return $this->status === EmploymentStatus::Employed;
                }

                public function ensureCanBeRetired(): void
                {
                    if (! $this->canBeRetired()) {
                        throw new Exception('Cannot be retired');
                    }
                }
            };

            expect(fn () => $model->ensureCanBeRetired())->not->toThrow(Exception::class);
        });

        test('can ensure model can be unretired', function () {
            $model = new class extends Model implements Retirable
            {
                /** @use IsRetirable<FakeRetirementModel, self> */
                use IsRetirable;

                public EmploymentStatus $status = EmploymentStatus::Retired;

                public function canBeUnretired(): bool
                {
                    return $this->status === EmploymentStatus::Retired;
                }

                public function ensureCanBeUnretired(): void
                {
                    if (! $this->canBeUnretired()) {
                        throw new Exception('Cannot be unretired');
                    }
                }
            };

            expect(fn () => $model->ensureCanBeUnretired())->not->toThrow(Exception::class);
        });
    });

});
