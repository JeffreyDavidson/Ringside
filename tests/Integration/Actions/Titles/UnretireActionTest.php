<?php

declare(strict_types=1);

use App\Actions\Titles\UnretireAction;
use App\Exceptions\Status\CannotBeUnretiredException;
use App\Models\Titles\Title;
use App\Repositories\TitleRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->titleRepository = $this->mock(TitleRepository::class);
});

test('it unretires a retired title and redirects', function () {
    $title = Title::factory()->retired()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldReceive('endRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($title);

    resolve(UnretireAction::class)->handle($title);
});

test('it unretires a retired title at a specific datetime', function () {
    $title = Title::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->titleRepository
        ->shouldReceive('endRetirement')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime))
        )
        ->andReturn($title);

    resolve(UnretireAction::class)->handle($title, $datetime);
});

test('it throws exception for unretiring a non unretirable title', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();

    resolve(UnretireAction::class)->handle($title);
})->throws(CannotBeUnretiredException::class)->with([
    'active',
    'inactive',
    'withFutureActivation',
    'unactivated',
]);
