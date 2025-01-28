<?php

declare(strict_types=1);

use App\Actions\Wrestlers\ClearInjuryAction;
use App\Events\Wrestlers\WrestlerClearedFromInjury;
use App\Exceptions\CannotBeClearedFromInjuryException;
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

test('it clears an injury of an injured wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('clearInjury')
        ->once()
        ->withArgs(function (Wrestler $unretireWrestler, Carbon $recoveryDate) use ($wrestler, $datetime) {
            expect($unretireWrestler->is($wrestler))->toBeTrue()
                ->and($recoveryDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($wrestler);

    app(ClearInjuryAction::class)->handle($wrestler);

    Event::assertDispatched(WrestlerClearedFromInjury::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->recoveryDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it clears an injury of an injured wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('clearInjury')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    app(ClearInjuryAction::class)->handle($wrestler, $datetime);

    Event::assertDispatched(WrestlerClearedFromInjury::class, function ($event) use ($wrestler, $datetime) {
        expect($event->wrestler->is($wrestler))->toBeTrue()
            ->and($event->recoveryDate->eq($datetime))->toBeTrue();

        return true;
    });
});

test('it throws exception for injuring a non injurable wrestler', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    app(ClearInjuryAction::class)->handle($wrestler);
})->throws(CannotBeClearedFromInjuryException::class)->with([
    'unemployed',
    'released',
    'withFutureEmployment',
    'bookable',
    'retired',
    'suspended',
]);
