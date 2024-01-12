<?php

declare(strict_types=1);

use App\Actions\Wrestlers\RetireAction;
use App\Events\Wrestlers\WrestlerRetired;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->wrestlerRepository = Mockery::mock(WrestlerRepository::class);
});

test('it retires a bookable wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldNotReceive('reinstate');

    $this->wrestlerRepository
        ->shouldNotReceive('clearInjury');

    $this->wrestlerRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Wrestler $releasableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($releasableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Wrestler $retirableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($retirableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    RetireAction::run($wrestler);

    Event::assertDispatched(WrestlerRetired::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it retires a bookable wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldNotReceive('reinstate');

    $this->wrestlerRepository
        ->shouldNotReceive('clearInjury');

    $this->wrestlerRepository
        ->shouldReceive('release')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('retire')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    RetireAction::run($wrestler, $datetime);

    Event::assertDispatched(WrestlerRetired::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it retires a suspended wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('reinstate')
        ->once()
        ->withArgs(function (Wrestler $reinstatableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($reinstatableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldNotReceive('clearInjury');

    $this->wrestlerRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Wrestler $releasableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($releasableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Wrestler $retirableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($retirableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    RetireAction::run($wrestler);

    Event::assertDispatched(WrestlerRetired::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it retires a suspended wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('reinstate')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldNotReceive('clearInjury');

    $this->wrestlerRepository
        ->shouldReceive('release')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('retire')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    RetireAction::run($wrestler, $datetime);

    Event::assertDispatched(WrestlerRetired::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it retires an injured wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldNotReceive('reinstate');

    $this->wrestlerRepository
        ->shouldReceive('clearInjury')
        ->once()
        ->withArgs(function (Wrestler $clearableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($clearableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Wrestler $releasableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($releasableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Wrestler $retirableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($retirableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    RetireAction::run($wrestler);

    Event::assertDispatched(WrestlerRetired::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it retires an injured wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldNotReceive('reinstate');

    $this->wrestlerRepository
        ->shouldReceive('clearInjury')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('release')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('retire')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    RetireAction::run($wrestler, $datetime);

    Event::assertDispatched(WrestlerRetired::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it retires a released wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->released()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldNotReceive('reinstate');

    $this->wrestlerRepository
        ->shouldNotReceive('clearInjury');

    $this->wrestlerRepository
        ->shouldNotReceive('release');

    $this->wrestlerRepository
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Wrestler $retirableWrestler, Carbon $retirementDate) use ($wrestler, $datetime) {
            expect($retirableWrestler->is($wrestler))->toBeTrue()
                ->and($retirementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    RetireAction::run($wrestler);

    Event::assertDispatched(WrestlerRetired::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it retires a released wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->released()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldNotReceive('reinstate');

    $this->wrestlerRepository
        ->shouldNotReceive('clearInjury');

    $this->wrestlerRepository
        ->shouldNotReceive('release');

    $this->wrestlerRepository
        ->shouldReceive('retire')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    RetireAction::run($wrestler, $datetime);

    Event::assertDispatched(WrestlerRetired::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->retirementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it throws exception trying to retire a non retirable wrestler', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    RetireAction::run($wrestler);
})->throws(CannotBeRetiredException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'retired',
]);
