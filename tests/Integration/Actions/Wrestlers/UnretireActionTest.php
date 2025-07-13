<?php

declare(strict_types=1);

use App\Actions\Wrestlers\UnretireAction;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
});

test('it unretires a retired wrestler at the current datetime by default', function () {
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

    resolve(UnretireAction::class)->handle($wrestler);
});

test('it unretires a retired wrestler at a specific datetime', function () {
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

    resolve(UnretireAction::class)->handle($wrestler, $datetime);
});

test('invoke throws exception for unretiring a non unretirable wrestler', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    resolve(UnretireAction::class)->handle($wrestler);
})->throws(CannotBeUnretiredException::class)->with([
    'bookable',
    'withFutureEmployment',
    'injured',
    'released',
    'suspended',
    'unemployed',
]);
