<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\IsRetirable;
use App\Models\Contracts\Retirable;
use App\Models\Lifecycle\Retirement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Tests\Unit\Models\Concerns\Support\RetirementStateModel;

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

    test('checks current retirement state', function () {
        $model = new RetirementStateModel();

        expect($model->isRetired())->toBeFalse();

        $model->currentRetirementExists = true;

        expect($model->isRetired())->toBeTrue();
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
