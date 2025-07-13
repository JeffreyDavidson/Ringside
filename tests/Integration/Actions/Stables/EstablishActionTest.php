<?php

declare(strict_types=1);

use App\Actions\Stables\EstablishAction;
use App\Exceptions\Status\CannotBeActivatedException;
use App\Models\Stables\Stable;
use App\Repositories\StableRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->stableRepository = $this->mock(StableRepository::class);
});

test('it establishes an unactivated stable and employs its unemployed members at the current datetime by default', function () {
    $stable = Stable::factory()->unactivated()->create();
    $datetime = now();

    $this->stableRepository
        ->shouldReceive('createActivity')
        ->once()
        ->withArgs(function (Stable $establishableStable, Carbon $establishDate) use ($stable, $datetime) {
            expect($establishableStable->is($stable))->toBeTrue()
                ->and($establishDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($stable);

    resolve(EstablishAction::class)->handle($stable);
});

test('it establishes an unactivated stable with members at a specific datetime', function () {
    $stable = Stable::factory()->unactivated()->create();
    $datetime = now()->addDays(2);

    $this->stableRepository
        ->shouldReceive('createActivity')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    resolve(EstablishAction::class)->handle($stable, $datetime);
});

test('it establishes a future activated stable and employs its unemployed members at the current datetime by default', function () {
    $stable = Stable::factory()->unactivated()->create();
    $datetime = now();

    $this->stableRepository
        ->shouldReceive('createActivity')
        ->once()
        ->withArgs(function (Stable $establishableStable, Carbon $establishDate) use ($stable, $datetime) {
            expect($establishableStable->is($stable))->toBeTrue()
                ->and($establishDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($stable);

    resolve(EstablishAction::class)->handle($stable);
});

test('it establishes a future activated stable with members at a specific datetime', function () {
    $stable = Stable::factory()->withFutureActivation()->create();
    $datetime = now()->addDays(2);

    $this->stableRepository
        ->shouldReceive('createActivity')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    resolve(EstablishAction::class)->handle($stable, $datetime);
});

test('it throws exception for establishing a non establishable stable', function ($factoryState) {
    $stable = Stable::factory()->{$factoryState}()->create();

    resolve(EstablishAction::class)->handle($stable);
})->throws(CannotBeActivatedException::class)->with([
    'active',
]);
