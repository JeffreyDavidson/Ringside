<?php

declare(strict_types=1);

use App\Actions\Stables\ActivateAction;
use App\Exceptions\CannotBeActivatedException;
use App\Models\Stables\Stable;
use App\Repositories\StableRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->stableRepository = $this->mock(StableRepository::class);
});

test('it activates an unactivated stable and employs its unemployed members at the current datetime by default', function () {
    $stable = Stable::factory()->unactivated()->create();
    $datetime = now();

    $this->stableRepository
        ->shouldReceive('activate')
        ->once()
        ->withArgs(function (Stable $activatableStable, Carbon $activationDate) use ($stable, $datetime) {
            expect($activatableStable->is($stable))->toBeTrue()
                ->and($activationDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($stable);

    resolve(ActivateAction::class)->handle($stable);
});

test('it activates an unactivated stable with members at a specific datetime', function () {
    $stable = Stable::factory()->unactivated()->create();
    $datetime = now()->addDays(2);

    $this->stableRepository
        ->shouldReceive('activate')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    resolve(ActivateAction::class)->handle($stable, $datetime);
});

test('it activates a future activated stable and employs its unemployed members at the current datetime by default', function () {
    $stable = Stable::factory()->unactivated()->create();
    $datetime = now();

    $this->stableRepository
        ->shouldReceive('activate')
        ->once()
        ->withArgs(function (Stable $activatableStable, Carbon $activationDate) use ($stable, $datetime) {
            expect($activatableStable->is($stable))->toBeTrue()
                ->and($activationDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($stable);

    resolve(ActivateAction::class)->handle($stable);
});

test('it activates a future activated stable with members at a specific datetime', function () {
    $stable = Stable::factory()->withFutureActivation()->create();
    $datetime = now()->addDays(2);

    $this->stableRepository
        ->shouldReceive('activate')
        ->once()
        ->with($stable, $datetime)
        ->andReturns($stable);

    resolve(ActivateAction::class)->handle($stable, $datetime);
})->skip();

test('it throws exception for activating a non activatable stable', function ($factoryState) {
    $stable = Stable::factory()->{$factoryState}()->create();

    resolve(ActivateAction::class)->handle($stable);
})->throws(CannotBeActivatedException::class)->with([
    'active',
]);
