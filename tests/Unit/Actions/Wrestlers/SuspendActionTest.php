<?php

declare(strict_types=1);

use App\Actions\Wrestlers\SuspendAction;
use App\Exceptions\Status\CannotBeSuspendedException;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
});

test('it suspends a bookable wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('createSuspension')
        ->once()
        ->withArgs(function (Wrestler $suspendableWrestler, Carbon $suspensionDate) use ($wrestler, $datetime) {
            expect($suspendableWrestler->is($wrestler))->toBeTrue()
                ->and($suspensionDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($wrestler);

    resolve(SuspendAction::class)->handle($wrestler);
});

test('it suspends a bookable wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('createSuspension')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    resolve(SuspendAction::class)->handle($wrestler, $datetime);
});

test('it throws exception for suspending a non suspendable wrestler', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    resolve(SuspendAction::class)->handle($wrestler);
})->throws(CannotBeSuspendedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'injured',
    'released',
    'retired',
    'suspended',
]);
