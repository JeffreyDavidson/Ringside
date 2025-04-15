<?php

declare(strict_types=1);

use App\Actions\Wrestlers\ReleaseAction;
use App\Events\Wrestlers\WrestlerReleased;
use App\Exceptions\CannotBeReleasedException;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
});

test('it releases a bookable wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('reinstate');

    $this->wrestlerRepository
        ->shouldNotReceive('clearInjury');

    $this->wrestlerRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Wrestler $releasableWrestler, Carbon $releaseDate) use ($wrestler, $datetime) {
            expect($releasableWrestler->is($wrestler))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($wrestler);

    ReleaseAction::run($wrestler);

    Event::assertDispatched(WrestlerReleased::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->releaseDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it releases an bookable wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('reinstate');

    $this->wrestlerRepository
        ->shouldNotReceive('clearInjury');

    $this->wrestlerRepository
        ->shouldReceive('release')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    ReleaseAction::run($wrestler, $datetime);

    Event::assertDispatched(WrestlerReleased::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->releaseDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it releases a suspended wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('reinstate')
        ->once()
        ->withArgs(function (Wrestler $reinstatableWrestler, Carbon $releaseDate) use ($wrestler, $datetime) {
            expect($reinstatableWrestler->is($wrestler))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldNotReceive('clearInjury');

    $this->wrestlerRepository
        ->shouldReceive('release')
        ->once()
        ->withArgs(function (Wrestler $releasableWrestler, Carbon $releaseDate) use ($wrestler, $datetime) {
            expect($releasableWrestler->is($wrestler))->toBeTrue()
                ->and($releaseDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($wrestler);

    ReleaseAction::run($wrestler);

    Event::assertDispatched(WrestlerReleased::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->releaseDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it releases a suspended wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('reinstate')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldNotReceive('clearInjury');

    $this->wrestlerRepository
        ->shouldReceive('release')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    ReleaseAction::run($wrestler, $datetime);

    Event::assertDispatched(WrestlerReleased::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->releaseDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('invoke throws an exception for releasing a non releasable wrestler', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    ReleaseAction::run($wrestler);
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
