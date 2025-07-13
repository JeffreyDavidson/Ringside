<?php

declare(strict_types=1);

use App\Actions\Wrestlers\ReinstateAction;
use App\Exceptions\Status\CannotBeReinstatedException;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
});

test('it reinstates a suspended wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    resolve(ReinstateAction::class)->handle($wrestler);
});

test('it reinstates a suspended wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    resolve(ReinstateAction::class)->handle($wrestler, $datetime);
});

test('it reinstates an injured wrestler at the current datetime by default', function () {
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

    resolve(ReinstateAction::class)->handle($wrestler);
});

test('it reinstates an injured wrestler at a specific datetime', function () {
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

    resolve(ReinstateAction::class)->handle($wrestler, $datetime);
});

test('it reinstates a wrestler with both suspension and injury', function () {
    $wrestler = Wrestler::factory()->suspended()->injured()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('endInjury')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    resolve(ReinstateAction::class)->handle($wrestler);
});

test('invoke throws exception for reinstating an unemployed wrestler', function () {
    $wrestler = Wrestler::factory()->unemployed()->create();
    $datetime = now();

    resolve(ReinstateAction::class)->handle($wrestler, $datetime);
})->throws(CannotBeReinstatedException::class);

test('invoke throws exception for reinstating a released wrestler', function () {
    $wrestler = Wrestler::factory()->released()->create();
    $datetime = now();

    resolve(ReinstateAction::class)->handle($wrestler, $datetime);
})->throws(CannotBeReinstatedException::class);

test('invoke throws exception for reinstating a retired wrestler', function () {
    $wrestler = Wrestler::factory()->retired()->create();
    $datetime = now();

    resolve(ReinstateAction::class)->handle($wrestler, $datetime);
})->throws(CannotBeReinstatedException::class);
