<?php

use App\Actions\Wrestlers\ReleaseAction;
use App\Actions\Wrestlers\RetireAction;
use App\Events\Wrestlers\WrestlerRetired;
use App\Exceptions\CannotBeRetiredException;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;

test('it retires a bookable wrestler at the current datetime by default', function () {
    Event::fake();

    testTime()->freeze();
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    mock(WrestlerRepository::class)
        ->shouldReceive('retire')
        ->once()
        ->withArgs(function (Wrestler $unretireWrestler, Carbon $suspensionDate) use ($wrestler, $datetime) {
            $this->assertTrue($unretireWrestler->is($wrestler));
            $this->assertTrue($suspensionDate->equalTo($datetime));

            return true;
        })
        ->andReturns($wrestler);

    ReleaseAction::shouldRun()
        ->withArgs(function (Wrestler $unretireWrestler, Carbon $employmentDate) use ($wrestler, $datetime) {
            $this->assertTrue($unretireWrestler->is($wrestler));
            $this->assertTrue($employmentDate->equalTo($datetime));

            return true;
        });

    RetireAction::run($wrestler);

    Event::assertDispatched(WrestlerRetired::class);
});

test('it retires a bookable wrestler at a specific datetime', function () {
    Event::fake();

    testTime()->freeze();
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    mock(WrestlerRepository::class)
        ->shouldReceive('retire')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    ReleaseAction::shouldRun($wrestler, $datetime);

    RetireAction::run($wrestler, $datetime);

    Event::assertDispatched(WrestlerRetired::class);
});

test('it throws exception trying to retire an unemployed wrestler', function () {
    $wrestler = Wrestler::factory()->unemployed()->create();

    RetireAction::run($wrestler);
})->throws(CannotBeRetiredException::class);

test('it throws exception trying to retire a wrestler with a future employment', function () {
    $wrestler = Wrestler::factory()->withFutureEmployment()->create();

    RetireAction::run($wrestler);
})->throws(CannotBeRetiredException::class);

test('it throws exception trying to retire a retired wrestler', function () {
    $wrestler = Wrestler::factory()->retired()->create();

    RetireAction::run($wrestler);
})->throws(CannotBeRetiredException::class);
