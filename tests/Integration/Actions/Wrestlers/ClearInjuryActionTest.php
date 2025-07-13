<?php

declare(strict_types=1);

use App\Actions\Wrestlers\ClearInjuryAction;
use App\Exceptions\Status\CannotBeClearedFromInjuryException;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
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
        ->shouldReceive('endInjury')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    resolve(ClearInjuryAction::class)->handle($wrestler);
});

test('it clears an injury of an injured wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->injured()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('endInjury')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    resolve(ClearInjuryAction::class)->handle($wrestler, $datetime);
});

test('it throws exception for injuring a non injurable wrestler', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    resolve(ClearInjuryAction::class)->handle($wrestler);
})->throws(CannotBeClearedFromInjuryException::class)->with([
    'unemployed',
    'released',
    'withFutureEmployment',
    'bookable',
    'retired',
    'suspended',
]);
