<?php

declare(strict_types=1);

use App\Actions\Stables\DisbandAction;
use App\Exceptions\Status\CannotBeDisbandedException;
use App\Models\Stables\Stable;
use App\Repositories\StableRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->stableRepository = $this->mock(StableRepository::class);
});

test('it disbands a stable at the current datetime by default', function () {
    $stable = Stable::factory()->active()->create();
    $datetime = now();

    $this->stableRepository
        ->shouldReceive('endActivity')
        ->once()
        ->withArgs(function (Stable $disbandableStable, Carbon $disbandDate) use ($stable, $datetime) {
            expect($disbandableStable->is($stable))->toBeTrue()
                ->and($disbandDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($stable);

    $this->stableRepository
        ->shouldReceive('disbandMembers')
        ->once()
        ->withArgs(function (Stable $disbandableStable, Carbon $disbandDate) use ($stable, $datetime) {
            expect($disbandableStable->is($stable))->toBeTrue()
                ->and($disbandDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($stable);

    resolve(DisbandAction::class)->handle($stable);
});

test('it disbands a stable at a specific datetime', function () {
    $stable = Stable::factory()->active()->create();
    $datetime = now()->addDays(2);

    $this->stableRepository
        ->shouldReceive('endActivity')
        ->once()
        ->with($stable, $datetime)
        ->andReturn($stable);

    $this->stableRepository
        ->shouldReceive('disbandMembers')
        ->once()
        ->with($stable, $datetime)
        ->andReturn($stable);

    resolve(DisbandAction::class)->handle($stable, $datetime);
});

test('it throws exception for disbanding a non disbandable stable', function ($factoryState) {
    $stable = Stable::factory()->{$factoryState}()->create();

    resolve(DisbandAction::class)->handle($stable);
})->throws(CannotBeDisbandedException::class)->with([
    'inactive',
    'retired',
    'unactivated',
    'withFutureActivation',
]);
