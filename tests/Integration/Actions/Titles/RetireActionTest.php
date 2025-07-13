<?php

declare(strict_types=1);

use App\Actions\Titles\RetireAction;
use App\Exceptions\Status\CannotBeRetiredException;
use App\Models\Titles\Title;
use App\Repositories\TitleRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->titleRepository = $this->mock(TitleRepository::class);
});

test('it retires an active title at the current datetime by default', function () {
    $title = Title::factory()->active()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldReceive('pull')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturns($title);

    $this->titleRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturns($title);

    resolve(RetireAction::class)->handle($title);
});

test('it retires an active title at a specific datetime', function () {
    $title = Title::factory()->active()->create();
    $datetime = now()->addDays(2);

    $this->titleRepository
        ->shouldReceive('pull')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturns($title);

    $this->titleRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturns($title);

    resolve(RetireAction::class)->handle($title, $datetime);
});

test('it retires an inactive title at the current datetime by default', function () {
    $title = Title::factory()->inactive()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldNotReceive('pull');

    $this->titleRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturns($title);

    resolve(RetireAction::class)->handle($title);
});

test('it retires an inactive title at a specific datetime', function () {
    $title = Title::factory()->inactive()->create();
    $datetime = now()->addDays(2);

    $this->titleRepository
        ->shouldNotReceive('pull');

    $this->titleRepository
        ->shouldReceive('createRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturns($title);

    resolve(RetireAction::class)->handle($title, $datetime);
});

test('it throws exception for unretiring a non unretirable title', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();

    resolve(RetireAction::class)->handle($title);
})->throws(CannotBeRetiredException::class)->with([
    'retired',
    'withFutureActivation',
    'unactivated',
]);
