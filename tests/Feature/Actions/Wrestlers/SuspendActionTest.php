<?php

use App\Actions\Wrestlers\SuspendAction;
use App\Events\Wrestlers\WrestlerSuspended;
use App\Exceptions\CannotBeSuspendedException;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->wrestlerRepository = mock(WrestlerRepository::class);
});

test('it suspends a bookable wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('suspend')
        ->once()
        ->withArgs(function (Wrestler $suspendableWrestler, Carbon $suspensionDate) use ($wrestler, $datetime) {
            expect($suspendableWrestler->is($wrestler))->toBeTrue();
            expect($suspensionDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($wrestler);

    SuspendAction::run($wrestler);

    Event::assertDispatched(WrestlerSuspended::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue();
        expect($event->suspensionDate->is($datetime))->toBeTrue();

        return true;
    });
});

test('it suspends a bookable wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('suspend')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    SuspendAction::run($wrestler, $datetime);

    Event::assertDispatched(WrestlerSuspended::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue();
        expect($event->suspensionDate->is($datetime))->toBeTrue();

        return true;
    });
});

test('invoke throws exception for suspending a non suspendable wrestler', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    SuspendAction::run($wrestler);
})->throws(CannotBeSuspendedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'injured',
    'released',
    'retired',
    'suspended',
]);
