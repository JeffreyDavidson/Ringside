<?php

declare(strict_types=1);

use App\Actions\Wrestlers\ReleaseAction;
use App\Exceptions\Status\CannotBeReleasedException;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
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
        ->shouldReceive('removeFromCurrentManagers')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentTagTeam')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentStable')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    resolve(ReleaseAction::class)->handle($wrestler);
});

test('it releases a bookable wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->bookable()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentManagers')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentTagTeam')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentStable')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    resolve(ReleaseAction::class)->handle($wrestler, $datetime);
});

test('it releases a suspended wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentManagers')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentTagTeam')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentStable')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    resolve(ReleaseAction::class)->handle($wrestler);
});

test('it releases a suspended wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->suspended()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentManagers')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentTagTeam')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('removeFromCurrentStable')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('endSuspension')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('endEmployment')
        ->once()
        ->with(
            Mockery::on(fn ($w) => $w->id === $wrestler->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($wrestler);

    resolve(ReleaseAction::class)->handle($wrestler, $datetime);
});

test('invoke throws an exception for releasing a non releasable wrestler', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    resolve(ReleaseAction::class)->handle($wrestler);
})->throws(CannotBeReleasedException::class)->with([
    'unemployed',
    'withFutureEmployment',
    'released',
    'retired',
]);
