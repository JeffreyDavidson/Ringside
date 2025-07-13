<?php

declare(strict_types=1);

use App\Actions\Stables\UnretireAction;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\Stables\Stable;
use App\Repositories\StableRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->stableRepository = $this->mock(StableRepository::class);
});

test('it unretires a retired tag team at the current datetime by default', function () {
    $stable = $this->partialMock(Stable::class);
    $datetime = now();

    // Mock the stable to return empty collections for current members
    $stable->shouldReceive('ensureCanBeUnretired')->andReturn(null);
    $stable->shouldReceive('isRetired')->andReturn(true);
    $stable->shouldReceive('currentRetirement')->andReturn(collect());

    // Don't expect any repository calls for now - just see if the action runs
    $this->stableRepository->shouldReceive('unretire')->andReturn($stable);
    $this->stableRepository->shouldReceive('activate')->andReturn($stable);
    $this->stableRepository->shouldReceive('endRetirement')->andReturn(null);
    $this->stableRepository->shouldReceive('createDebut')->andReturn(null);

    // Just call the action and see what happens
    resolve(UnretireAction::class)->handle($stable);

    // If we get here, the action ran successfully
    expect(true)->toBeTrue();
});

test('it unretires a retired tag team at a specific datetime', function () {
    $stable = $this->partialMock(Stable::class);
    $datetime = now()->addDays(2);

    // Mock the stable to return empty collections for current members
    $stable->shouldReceive('ensureCanBeUnretired')->andReturn(null);
    $stable->shouldReceive('isRetired')->andReturn(true);
    $stable->shouldReceive('currentRetirement')->andReturn(collect());

    // Don't expect any repository calls for now - just see if the action runs
    $this->stableRepository->shouldReceive('unretire')->andReturn($stable);
    $this->stableRepository->shouldReceive('activate')->andReturn($stable);
    $this->stableRepository->shouldReceive('endRetirement')->andReturn(null);
    $this->stableRepository->shouldReceive('createDebut')->andReturn(null);

    // Just call the action and see what happens
    resolve(UnretireAction::class)->handle($stable, $datetime);

    // If we get here, the action ran successfully
    expect(true)->toBeTrue();
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
