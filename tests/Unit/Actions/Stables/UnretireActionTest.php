<?php

declare(strict_types=1);

use App\Actions\Stables\UnretireAction;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\Stables\Stable;
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
        ->shouldReceive('endRetirement')
        ->once()
        ->withArgs(function (Stable $unretirableStable, Carbon $unretireDate) use ($stable, $datetime) {
            expect($unretirableStable->is($stable))->toBeTrue()
                ->and($unretireDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($stable);

    resolve(UnretireAction::class)->handle($stable);
});

test('it unretires a retired tag team at a specific datetime', function () {
    $stable = Stable::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->stableRepository
        ->shouldReceive('endRetirement')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    resolve(UnretireAction::class)->handle($stable, $datetime);
});

test('it throws exception for unretiring a non unretirable stable', function ($factoryState) {
    $stable = Stable::factory()->{$factoryState}()->create();

    resolve(UnretireAction::class)->handle($stable);
})->throws(CannotBeUnretiredException::class)->with([
    'active',
    'withFutureActivation',
    'inactive',
    'unactivated',
]);
