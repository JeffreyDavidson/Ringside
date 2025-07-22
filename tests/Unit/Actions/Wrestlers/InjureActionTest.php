<?php

declare(strict_types=1);

use App\Actions\Wrestlers\InjureAction;
use App\Exceptions\Status\CannotBeInjuredException;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
});

test('it injures a bookable wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('createInjury')
        ->once()
        ->withArgs(function (Wrestler $injurableWrestler, Carbon $injuryDate) use ($wrestler, $datetime) {
            expect($injurableWrestler->is($wrestler))->toBeTrue()
                ->and($injuryDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($wrestler);

    resolve(InjureAction::class)->handle($wrestler);
});

test('it injures a bookable wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('createInjury')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    resolve(InjureAction::class)->handle($wrestler, $datetime);
});

test('invoke throws exception for injuring a non injurable wrestler', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    resolve(InjureAction::class)->handle($wrestler);
})->throws(CannotBeInjuredException::class)->with([
    'unemployed',
    'suspended',
    'released',
    'withFutureEmployment',
    'retired',
    'injured',
]);
