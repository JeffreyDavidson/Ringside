<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Concerns;

use App\Models\Concerns\IsInjurable;
use App\Models\Contracts\Injurable;
use App\Models\Lifecycle\Injury;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Tests\Unit\Models\Concerns\Support\InjuryStateModel;

describe('IsInjurable', function () {
    test('provides polymorphic injury relationships', function () {
        $model = new class extends Model implements Injurable
        {
            /** @use IsInjurable<self> */
            use IsInjurable;
        };

        expect($model->injuries())->toBeInstanceOf(MorphMany::class)
            ->and($model->currentInjury())->toBeInstanceOf(MorphOne::class)
            ->and($model->previousInjuries())->toBeInstanceOf(MorphMany::class)
            ->and($model->previousInjury())->toBeInstanceOf(MorphOne::class)
            ->and($model->injuries()->getRelated())->toBeInstanceOf(Injury::class);
    });

    test('checks current injury state', function () {
        $model = new InjuryStateModel();

        expect($model->isInjured())->toBeFalse();

        $model->currentInjuryExists = true;

        expect($model->isInjured())->toBeTrue();
    });

    test('constrains current and previous injury relationships', function () {
        $model = new class extends Model implements Injurable
        {
            /** @use IsInjurable<self> */
            use IsInjurable;
        };

        $currentWheres = $model->currentInjury()->getQuery()->getQuery()->wheres;
        $previousWheres = $model->previousInjuries()->getQuery()->getQuery()->wheres;

        expect(collect($currentWheres)->contains(
            fn (array $where): bool => $where['type'] === 'Null' && $where['column'] === 'ended_at'
        ))->toBeTrue()->and(collect($previousWheres)->contains(
            fn (array $where): bool => $where['type'] === 'NotNull' && $where['column'] === 'ended_at'
        ))->toBeTrue();
    });
});
