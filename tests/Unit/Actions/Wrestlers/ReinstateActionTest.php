<?php

declare(strict_types=1);

use App\Actions\Wrestlers\ReinstateAction;
use App\Exceptions\Status\CannotBeReinstatedException;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
});

test('it reinstates a suspended wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->withArgs(function (Wrestler $reinstatableWrestler, Carbon $reinstatementDate) use ($wrestler, $datetime) {
            expect($reinstatableWrestler->is($wrestler))->toBeTrue()
                ->and($reinstatementDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($wrestler);

    resolve(ReinstateAction::class)->handle($wrestler);
});

test('it reinstates a suspended wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    resolve(ReinstateAction::class)->handle($wrestler, $datetime);
});

test('invoke throws exception for reinstating a non reinstatable wrestler', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();
    $datetime = now();

    resolve(ReinstateAction::class)->handle($wrestler, $datetime);
})->throws(CannotBeReinstatedException::class)->with([
    'bookable',
    'unemployed',
    'released',
    'withFutureEmployment',
    'retired',
]);
