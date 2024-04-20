<?php

declare(strict_types=1);

use App\Actions\Stables\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\Stable;
use App\Repositories\StableRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->stableRepository = $this->mock(StableRepository::class);
});

test('it unretires a retired tag team at the current datetime by default', function () {
    $stable = Stable::factory()->retired()->create();
    $datetime = now();

    $this->stableRepository
        ->shouldReceive('unretire')
        ->once()
        ->withArgs(function (Stable $unretirableStable, Carbon $unretireDate) use ($stable, $datetime) {
            expect($unretirableStable->is($stable))->toBeTrue()
                ->and($unretireDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($stable);

    $this->stableRepository
        ->shouldReceive('activate')
        ->once()
        ->withArgs(function (Stable $employableStable, Carbon $employmentDate) use ($stable, $datetime) {
            expect($employableStable->is($stable))->toBeTrue()
                ->and($employmentDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($stable);

    UnretireAction::run($stable);
});

test('it unretires a retired tag team at a specific datetime', function () {
    $stable = Stable::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->stableRepository
        ->shouldReceive('unretire')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    $this->stableRepository
        ->shouldReceive('activate')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    UnretireAction::run($stable, $datetime);
});

test('it throws exception for unretiring a non unretirable stable', function ($factoryState) {
    $stable = Stable::factory()->{$factoryState}()->create();

    UnretireAction::run($stable);
})->throws(CannotBeUnretiredException::class)->with([
    'active',
    'withFutureActivation',
    'inactive',
    'unactivated',
]);
