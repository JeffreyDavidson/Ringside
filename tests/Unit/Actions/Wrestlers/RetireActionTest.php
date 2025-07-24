<?php

declare(strict_types=1);

use App\Actions\Wrestlers\RetireAction;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\StableRepository;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
    $this->stableRepository = $this->mock(StableRepository::class);

    // Add default expectations for complex methods that are always called
    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentTagTeam')
        ->byDefault();

    // Add default expectation for stable removal calls
    $this->stableRepository
        ->shouldReceive('removeWrestler')
        ->byDefault();

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentStable')
        ->byDefault();

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentManagers')
        ->byDefault();
});

test('it retires a bookable wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (Wrestler $releasableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($releasableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (Wrestler $retirableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($retirableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    resolve(RetireAction::class)->handle($wrestler);
});

test('it retires a bookable wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    resolve(RetireAction::class)->handle($wrestler, $datetime);
});

test('it retires a suspended wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->withArgs(function (Wrestler $reinstatableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($reinstatableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (Wrestler $releasableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($releasableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (Wrestler $retirableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($retirableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    resolve(RetireAction::class)->handle($wrestler);
});

test('it retires a suspended wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    resolve(RetireAction::class)->handle($wrestler, $datetime);
});

test('it retires an injured wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('endInjury')
        ->once()
        ->withArgs(function (Wrestler $clearableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($clearableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->withArgs(function (Wrestler $releasableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($releasableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (Wrestler $retirableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($retirableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    resolve(RetireAction::class)->handle($wrestler);
});

test('it retires an injured wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('endInjury')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    resolve(RetireAction::class)->handle($wrestler, $datetime);
});

test('it retires a released wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->released()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->withArgs(function (Wrestler $retirableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($retirableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    resolve(RetireAction::class)->handle($wrestler);
});

test('it retires a released wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->released()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    resolve(RetireAction::class)->handle($wrestler, $datetime);
});

test('it throws exception trying to retire a non retirable wrestler', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    resolve(RetireAction::class)->handle($wrestler);
})->throws(CannotBeRetiredException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'retired',
]);
