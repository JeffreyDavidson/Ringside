<?php

declare(strict_types=1);

use App\Actions\Wrestlers\EmployAction;
use App\Exceptions\Status\CannotBeEmployedException;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerEmployment;
use App\Repositories\WrestlerRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
});

test('it employs an employable wrestler at the current datetime by default', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();
    $wrestler = $wrestler->fresh();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    resolve(EmployAction::class)->handle($wrestler);
})->with([
    'unemployed',
    'released',
]);

test('it employs an employable wrestler at a specific datetime', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();
    $wrestler = $wrestler->fresh();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    resolve(EmployAction::class)->handle($wrestler, $datetime);
})->with([
    'unemployed',
    'released',
]);

test('it employs a retired wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->retired()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('endRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    resolve(EmployAction::class)->handle($wrestler);
});

test('it employs a retired wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('endRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    resolve(EmployAction::class)->handle($wrestler, $datetime);
});

test('it throws exception for employing a non employable wrestler', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();
    $wrestler = $wrestler->fresh();
    if ($factoryState === 'employed') {
        WrestlerEmployment::factory()->for($wrestler)->create(['ended_at' => null]);
        $wrestler = Wrestler::with('employments')->find($wrestler->id);
    }

    $this->wrestlerRepository
        ->shouldReceive('createEmployment')
        ->zeroOrMoreTimes();

    resolve(EmployAction::class)->handle($wrestler);
})->throws(CannotBeEmployedException::class)->with([
    'withFutureEmployment',
]);
