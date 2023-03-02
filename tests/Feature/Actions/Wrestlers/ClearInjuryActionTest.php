<?php

use App\Actions\Wrestlers\ClearInjuryAction;
use App\Events\Wrestlers\WrestlerClearedFromInjury;
use App\Exceptions\CannotBeClearedFromInjuryException;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;

test('it clears an injury of an injured wrestler at the current datetime by default', function () {
    Event::fake();

    testTime()->freeze();
    $wrestler = Wrestler::factory()->injured()->create();
    $datetime = now();

    mock(WrestlerRepository::class)
        ->shouldReceive('clearInjury')
        ->once()
        ->withArgs(function (Wrestler $unretireWrestler, Carbon $recoveryDate) use ($wrestler, $datetime) {
            $this->assertTrue($unretireWrestler->is($wrestler));
            $this->assertTrue($recoveryDate->equalTo($datetime));

            return true;
        })
        ->andReturn($wrestler);

    ClearInjuryAction::run($wrestler);

    Event::assertDispatched(WrestlerClearedFromInjury::class);
});

test('it clears an injury of an injured wrestler at a specific datetime', function () {
    Event::fake();

    testTime()->freeze();
    $wrestler = Wrestler::factory()->injured()->create();
    $datetime = now()->addDays(2);

    mock(WrestlerRepository::class)
        ->shouldReceive('clearInjury')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    ClearInjuryAction::run($wrestler, $datetime);

    Event::assertDispatched(WrestlerClearedFromInjury::class);
});

test('it throws exception for injuring a non injurable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    ClearInjuryAction::run($wrestler);
})->throws(CannotBeClearedFromInjuryException::class)->with([
    'unemployed',
    'released',
    'withFutureEmployment',
    'bookable',
    'retired',
    'suspended',
]);
