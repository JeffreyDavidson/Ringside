<?php

declare(strict_types=1);

use App\Actions\Wrestlers\InjureAction;
use App\Exceptions\Status\CannotBeInjuredException;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
});

test('it injures a bookable wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('createInjury')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    resolve(InjureAction::class)->handle($wrestler);
});

test('it injures a bookable wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('createInjury')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
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
