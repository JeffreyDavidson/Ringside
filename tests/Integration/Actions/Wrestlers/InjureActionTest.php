<?php

declare(strict_types=1);

use App\Actions\Wrestlers\InjureAction;
use App\Events\Wrestlers\WrestlerInjured;
use App\Exceptions\CannotBeInjuredException;
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

test('it injures a bookable wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('injure')
        ->once()
        ->withArgs(function (Wrestler $injurableWrestler, Carbon $injuryDate) use ($wrestler, $datetime) {
            expect($injurableWrestler->is($wrestler))->toBeTrue()
                ->and($injuryDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($wrestler);

    InjureAction::run($wrestler);

    Event::assertDispatched(WrestlerInjured::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->injureDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it injures a bookable wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('injure')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    InjureAction::run($wrestler, $datetime);

    Event::assertDispatched(WrestlerInjured::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->injureDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('invoke throws exception for injuring a non injurable wrestler', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    InjureAction::run($wrestler);
})->throws(CannotBeInjuredException::class)->with([
    'unemployed',
    'suspended',
    'released',
    'withFutureEmployment',
    'retired',
    'injured',
]);
