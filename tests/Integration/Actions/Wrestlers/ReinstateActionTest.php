<?php

declare(strict_types=1);

use App\Actions\Wrestlers\ReinstateAction;
use App\Events\Wrestlers\WrestlerReinstated;
use App\Exceptions\CannotBeReinstatedException;
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

test('it reinstates a suspended wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('reinstate')
        ->once()
        ->withArgs(function (Wrestler $reinstatableWrestler, Carbon $reinstatementDate) use ($wrestler, $datetime) {
            expect($reinstatableWrestler->is($wrestler))->toBeTrue()
                ->and($reinstatementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($wrestler);

    ReinstateAction::run($wrestler);

    Event::assertDispatched(WrestlerReinstated::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->reinstatementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it reinstates a suspended wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('reinstate')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    ReinstateAction::run($wrestler, $datetime);

    Event::assertDispatched(WrestlerReinstated::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->reinstatementDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('invoke throws exception for reinstating a non reinstatable wrestler', function ($factoryState) {
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
