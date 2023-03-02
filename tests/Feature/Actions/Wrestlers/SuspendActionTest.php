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

test('it suspends a bookable wrestler at the current datetime by default', function () {
    Event::fake();

    testTime()->freeze();
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    mock(WrestlerRepository::class)
        ->shouldReceive('suspend')
        ->once()
        ->withArgs(function (Wrestler $unretireWrestler, Carbon $suspensionDate) use ($wrestler, $datetime) {
            $this->assertTrue($unretireWrestler->is($wrestler));
            $this->assertTrue($suspensionDate->equalTo($datetime));

            return true;
        })
        ->andReturn($wrestler);

    SuspendAction::run($wrestler);

    Event::assertDispatched(WrestlerSuspended::class);
});

test('it suspends a bookable wrestler at a specific datetime', function () {
    Event::fake();

    testTime()->freeze();
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    mock(WrestlerRepository::class)
        ->shouldReceive('suspend')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    SuspendAction::run($wrestler, $datetime);

    Event::assertDispatched(WrestlerSuspended::class);
});

test('invoke throws exception for suspending a non suspendable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

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
