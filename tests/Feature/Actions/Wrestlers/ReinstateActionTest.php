<?php

use App\Actions\Wrestlers\ReinstateAction;
use App\Events\Wrestlers\WrestlerReinstated;
use App\Exceptions\CannotBeReinstatedException;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;

test('it reinstates a suspended wrestler at the current datetime by default', function () {
    Event::fake();

    testTime()->freeze();
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now();

    mock(WrestlerRepository::class)
        ->shouldReceive('reinstate')
        ->once()
        ->withArgs(function (Wrestler $unretireWrestler, Carbon $reinstatementDate) use ($wrestler, $datetime) {
            $this->assertTrue($unretireWrestler->is($wrestler));
            $this->assertTrue($reinstatementDate->equalTo($datetime));

            return true;
        })
        ->andReturn($wrestler);

    ReinstateAction::run($wrestler);

    Event::assertDispatched(WrestlerReinstated::class);
});

test('it reinstates a suspended wrestler at a specific datetime', function () {
    Event::fake();

    testTime()->freeze();
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    mock(WrestlerRepository::class)
        ->shouldReceive('reinstate')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    ReinstateAction::run($wrestler, $datetime);

    Event::assertDispatched(WrestlerReinstated::class);
});

test('invoke throws exception for reinstating a non reinstatable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();
    $datetime = now();

    ReinstateAction::run($wrestler, $datetime);
})->throws(CannotBeReinstatedException::class)->with([
    'bookable',
    'unemployed',
    'injured',
    'released',
    'withFutureEmployment',
    'retired',
]);
