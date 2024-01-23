<?php

declare(strict_types=1);

use App\Actions\Stables\DeactivateAction;
use App\Exceptions\CannotBeDeactivatedException;
use App\Models\Stable;
use App\Repositories\StableRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->stableRepository = Mockery::mock(StableRepository::class);
});

test('it deactivates a stable at the current datetime by default', function () {
    $stable = Stable::factory()->active()->create();
    $datetime = now();

    $this->stableRepository
        ->shouldReceive('deactivate')
        ->once()
        ->withArgs(function (Stable $deactivatableStable, Carbon $deactivationDate) use ($stable, $datetime) {
            expect($deactivatableStable->is($stable))->toBeTrue()
                ->and($deactivationDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($stable);

    $this->stableRepository
        ->shouldReceive('disassemble')
        ->once()
        ->withArgs(function (Stable $deactivatableStable, Carbon $deactivationDate) use ($stable, $datetime) {
            expect($deactivatableStable->is($stable))->toBeTrue()
                ->and($deactivationDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($stable);

    DeactivateAction::run($stable);
});

test('it deactivates a stable at a specific datetime', function () {
    $stable = Stable::factory()->active()->create();
    $datetime = now()->addDays(2);

    $this->stableRepository
        ->shouldReceive('deactivate')
        ->once()
        ->with($stable, $datetime)
        ->andReturn($stable);

    $this->stableRepository
        ->shouldReceive('disassemble')
        ->once()
        ->with($stable, $datetime)
        ->andReturn($stable);

    DeactivateAction::run($stable, $datetime);
});

test('it throws exception for deactivating a non deactivatable stable', function ($factoryState) {
    $stable = Stable::factory()->{$factoryState}()->create();

    DeactivateAction::run($stable);
})->throws(CannotBeDeactivatedException::class)->with([
    'inactive',
    'retired',
    'unactivated',
    'withFutureActivation',
]);
