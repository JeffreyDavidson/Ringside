<?php

declare(strict_types=1);

use App\Actions\Wrestlers\ReleaseAction;
use App\Exceptions\Status\CannotBeReleasedException;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
});

test('it releases a bookable wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentTagTeam')
        ->once();

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentStable')
        ->once();

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentManagers')
        ->once();

    $this->wrestlerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (Wrestler $releasableWrestler, Carbon $releaseDate) use ($wrestler, $datetime) {
            expect($releasableWrestler->is($wrestler))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($wrestler);

    resolve(ReleaseAction::class)->handle($wrestler);
});

test('it releases an bookable wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentTagTeam')
        ->once();

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentStable')
        ->once();

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentManagers')
        ->once();

    $this->wrestlerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    resolve(ReleaseAction::class)->handle($wrestler, $datetime);
});

test('it releases a suspended wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentTagTeam')
        ->once();

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentStable')
        ->once();

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentManagers')
        ->once();

    $this->wrestlerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->withArgs(function (Wrestler $reinstatableWrestler, Carbon $releaseDate) use ($wrestler, $datetime) {
            expect($reinstatableWrestler->is($wrestler))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (Wrestler $releasableWrestler, Carbon $releaseDate) use ($wrestler, $datetime) {
            expect($releasableWrestler->is($wrestler))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($wrestler);

    resolve(ReleaseAction::class)->handle($wrestler);
});

test('it releases a suspended wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentTagTeam')
        ->once();

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentStable')
        ->once();

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentManagers')
        ->once();

    $this->wrestlerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    resolve(ReleaseAction::class)->handle($wrestler, $datetime);
});

test('invoke throws an exception for releasing a non releasable wrestler', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    resolve(ReleaseAction::class)->handle($wrestler);
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
