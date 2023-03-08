<?php

use App\Actions\Wrestlers\InjureAction;
use App\Events\Wrestlers\WrestlerInjured;
use App\Exceptions\CannotBeInjuredException;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use function Pest\Laravel\mock;
use function PHPUnit\Framework\assertTrue;
use function Spatie\PestPluginTestTime\testTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

test('it injures a bookable wrestler at the current datetime by default', function () {
    Event::fake();

    testTime()->freeze();
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    mock(WrestlerRepository::class)
        ->shouldReceive('injure')
        ->once()
        ->withArgs(function (Wrestler $unretireWrestler, Carbon $injuryDate) use ($wrestler, $datetime) {
            $this->assertTrue($unretireWrestler->is($wrestler));
            $this->assertTrue($injuryDate->equalTo($datetime));

            return true;
        })
        ->andReturn($wrestler);

    InjureAction::run($wrestler);

    Event::assertDispatched(WrestlerInjured::class, function ($event) use ($tagTeam, $datetime) {
        assertTrue($event->tagTeam->is($tagTeam));
        assertTrue($event->retirementDate->is($datetime));
    });
});

test('it injures a bookable wrestler at a specific datetime', function () {
    Event::fake();

    testTime()->freeze();
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    mock(WrestlerRepository::class)
        ->shouldReceive('injure')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    InjureAction::run($wrestler, $datetime);

    Event::assertDispatched(WrestlerInjured::class);
});

test('invoke throws exception for injuring a non injurable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    InjureAction::run($wrestler);
})->throws(CannotBeInjuredException::class)->with([
    'unemployed',
    'suspended',
    'released',
    'withFutureEmployment',
    'retired',
    'injured',
])->skip();
