<?php

declare(strict_types=1);

namespace Tests\Integration\Models\Concerns;

use App\Models\Concerns\IsSuspendable;
use App\Models\Contracts\Suspendable;
use App\Models\Lifecycle\Suspension;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Tests\Integration\Models\Concerns\Support\SuspensionStateModel;

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

        expect($model->currentSuspension()->exists())->toBeFalse();

        $model->currentSuspensionExists = true;

        expect($model->currentSuspension()->exists())->toBeTrue();
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
