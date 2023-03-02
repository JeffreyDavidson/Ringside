<?php

use App\Actions\Wrestlers\ReleaseAction;
use App\Events\Wrestlers\WrestlerReleased;
use App\Exceptions\CannotBeReleasedException;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;

test('it releases an employed wrestler at the current datetime by default', function () {
    Event::fake();

    testTime()->freeze();
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    mock(WrestlerRepository::class)
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Wrestler $unretireWrestler, Carbon $releaseDate) use ($wrestler, $datetime) {
            $this->assertTrue($unretireWrestler->is($wrestler));
            $this->assertTrue($releaseDate->equalTo($datetime));

            return true;
        })
        ->andReturn($wrestler);

    ReleaseAction::run($wrestler);

    Event::assertDispatched(WrestlerReleased::class);
});

test('it releases an employed wrestler at a specific datetime', function () {
    Event::fake();

    testTime()->freeze();
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    mock(WrestlerRepository::class)
        ->shouldReceive('release')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    ReleaseAction::run($wrestler, $datetime);

    Event::assertDispatched(WrestlerReleased::class);
});

test('invoke throws an exception for releasing a non releasable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();
    $datetime = now();

    ReleaseAction::run($wrestler, $datetime);
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
