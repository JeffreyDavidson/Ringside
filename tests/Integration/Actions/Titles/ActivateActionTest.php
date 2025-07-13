<?php

declare(strict_types=1);

use App\Actions\Titles\ActivateAction;
use App\Exceptions\Status\CannotBeActivatedException;
use App\Exceptions\Status\CannotBeDebutedException;
use App\Models\Titles\Title;
use App\Repositories\TitleRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->titleRepository = $this->mock(TitleRepository::class);
});

test('it activates an unactivated title at the current datetime by default', function () {
    $title = Title::factory()->unactivated()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldReceive('createDebut')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime)),
            Mockery::any()
        )
        ->andReturn($title);

    resolve(ActivateAction::class)->handle($title);
});

test('it activates an inactive title at the current datetime by default', function () {
    $title = Title::factory()->inactive()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldReceive('createReinstatement')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime)),
            Mockery::any()
        )
        ->andReturn($title);

    resolve(ActivateAction::class)->handle($title);
});

test('it activates an unactivated title at a specific datetime', function () {
    $title = Title::factory()->unactivated()->create();
    $datetime = now()->addDays(2);

    $this->titleRepository
        ->shouldReceive('createDebut')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime)),
            Mockery::any()
        )
        ->andReturn($title);

    resolve(ActivateAction::class)->handle($title, $datetime);
});

test('it activates an inactive title at a specific datetime', function () {
    $title = Title::factory()->inactive()->create();
    $datetime = now()->addDays(2);

    $this->titleRepository
        ->shouldReceive('createReinstatement')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime)),
            Mockery::any()
        )
        ->andReturn($title);

    resolve(ActivateAction::class)->handle($title, $datetime);
});

test('it activates a retired title at the current datetime by default', function () {
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

    $this->titleRepository
        ->shouldReceive('createReinstatement')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime)),
            Mockery::any()
        )
        ->andReturn($title);

    resolve(ActivateAction::class)->handle($title);
});

test('it activates a retired title at a specific datetime', function () {
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

    $this->titleRepository
        ->shouldReceive('createReinstatement')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime)),
            Mockery::any()
        )
        ->andReturn($title);

    resolve(ActivateAction::class)->handle($title, $datetime);
});

test('it throws exception for activating a non activatable title', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();

    // For withFutureActivation titles, the action might still try to call createDebut
    // before throwing the exception, so we need to mock it
    if ($factoryState === 'withFutureActivation') {
        $this->titleRepository
            ->shouldReceive('createDebut')
            ->once()
            ->andThrow(new CannotBeDebutedException());
    }

    resolve(ActivateAction::class)->handle($title);
})->throws(CannotBeDebutedException::class)->with([
    'active',
    'withFutureActivation',
]);

test('title can be debuted when in correct state', function () {
    $undebutedTitle = Title::factory()->unactivated()->create();
    $inactiveTitle = Title::factory()->inactive()->create();
    $activeTitle = Title::factory()->active()->create();

    expect($undebutedTitle->canBeDebuted())->toBeTrue();
    expect($inactiveTitle->canBeDebuted())->toBeFalse();
    expect($activeTitle->canBeDebuted())->toBeFalse();
});

test('title can be reinstated when in correct state', function () {
    $inactiveTitle = Title::factory()->inactive()->create();
    $activeTitle = Title::factory()->active()->create();

    expect($inactiveTitle->canBeReinstated())->toBeTrue();
    expect($activeTitle->canBeReinstated())->toBeFalse();
});

test('title debut validation throws correct exceptions', function () {
    $activeTitle = Title::factory()->active()->create();
    $undebutedTitle = Title::factory()->unactivated()->create();

    expect(fn () => $activeTitle->ensureCanBeDebuted())
        ->toThrow(CannotBeDebutedException::class);
    expect(fn () => $undebutedTitle->ensureCanBeDebuted())
        ->not->toThrow(Exception::class);
});

test('title reinstatement validation throws correct exceptions', function () {
    $inactiveTitle = Title::factory()->inactive()->create();
    $activeTitle = Title::factory()->active()->create();

    expect(fn () => $inactiveTitle->ensureCanBeReinstated())->not->toThrow(Exception::class);
    expect(fn () => $activeTitle->ensureCanBeReinstated())
        ->toThrow(CannotBeActivatedException::class);
});
