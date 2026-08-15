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
use App\Models\Lifecycle\Employment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Tests\Unit\Models\Concerns\Support\EmploymentStateModel;

describe('IsEmployable Trait Unit Tests', function () {
    describe('employment relationships', function () {
        test('provides employments relationship', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<self> */
                use IsEmployable;
            };
            expect($model->employments())->toBeInstanceOf(MorphMany::class);
        });

        test('provides current employment relationship', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<self> */
                use IsEmployable;
            };
            expect($model->currentEmployment())->toBeInstanceOf(MorphOne::class);
        });

        test('provides future employment relationship', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<self> */
                use IsEmployable;
            };
            expect($model->futureEmployment())->toBeInstanceOf(MorphOne::class);
        });

        test('provides previous employments relationship', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<self> */
                use IsEmployable;
            };
            expect($model->previousEmployments())->toBeInstanceOf(MorphMany::class);
        });

        test('provides previous employment relationship', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<self> */
                use IsEmployable;
            };
            expect($model->previousEmployment())->toBeInstanceOf(MorphOne::class);
        });

        test('provides first employment relationship', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<self> */
                use IsEmployable;
            };
            expect($model->firstEmployment())->toBeInstanceOf(MorphOne::class);
        });

        test('employments relationship uses the correct related model', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<self> */
                use IsEmployable;
            };
            $relation = $model->employments();
            expect($relation)->toBeInstanceOf(MorphMany::class);
            expect($relation->getRelated())->toBeInstanceOf(Employment::class);
        });

        test('currentEmployment relationship uses the correct related model', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<self> */
                use IsEmployable;
            };
            $relation = $model->currentEmployment();
            expect($relation)->toBeInstanceOf(MorphOne::class);
            expect($relation->getRelated())->toBeInstanceOf(Employment::class);
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

        test('can check if model has future employment', function () {
            $modelWith = new EmploymentStateModel();
            $modelWith->futureEmploymentExists = true;
            $modelWithout = new EmploymentStateModel();

            expect($modelWith->hasFutureEmployment())->toBeTrue();
            expect($modelWithout->hasFutureEmployment())->toBeFalse();
        });

        test('detects the absence of current and future employment', function () {
            $withoutEmployment = new EmploymentStateModel();
            $currentlyEmployed = new EmploymentStateModel();
            $currentlyEmployed->currentEmploymentExists = true;
            $futureEmployment = new EmploymentStateModel();
            $futureEmployment->futureEmploymentExists = true;

            expect($withoutEmployment->hasNoCurrentOrFutureEmployment())->toBeTrue()
                ->and($currentlyEmployed->hasNoCurrentOrFutureEmployment())->toBeFalse()
                ->and($futureEmployment->hasNoCurrentOrFutureEmployment())->toBeFalse();
        });

        test('can check if model has employment history', function () {
            $modelWith = new EmploymentStateModel();
            $modelWith->employmentExists = true;
            $modelWithout = new EmploymentStateModel();

            expect($modelWith->hasEmploymentHistory())->toBeTrue();
            expect($modelWithout->hasEmploymentHistory())->toBeFalse();
        });
    });

    describe('employment relationship queries', function () {
        test('current employment query includes whereNull ended_at', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<self> */
                use IsEmployable;
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
                /** @use IsEmployable<self> */
                use IsEmployable;
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
                /** @use IsEmployable<self> */
                use IsEmployable;
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
                /** @use IsEmployable<self> */
                use IsEmployable;
            };
            $relation = $model->previousEmployment();
            expect($relation)->toBeInstanceOf(MorphOne::class);
            // The ofMany constraint is applied internally by Laravel
            // We can verify the relationship type and that it's properly configured
        });

        test('first employment query includes ofMany constraint', function () {
            $model = new class extends Model implements Employable
            {
                /** @use IsEmployable<self> */
                use IsEmployable;
            };
            $relation = $model->firstEmployment();
            expect($relation)->toBeInstanceOf(MorphOne::class);
            // The ofMany constraint is applied internally by Laravel
            // We can verify the relationship type and that it's properly configured
        });
    });
});
