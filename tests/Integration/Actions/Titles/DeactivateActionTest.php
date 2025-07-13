<?php

declare(strict_types=1);

use App\Actions\Titles\DeactivateAction;
use App\Exceptions\Status\CannotBeDeactivatedException;
use App\Exceptions\Status\CannotBePulledException;
use App\Models\Titles\Title;
use App\Repositories\TitleRepository;
use Illuminate\Support\Facades\Event;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->titleRepository = $this->mock(TitleRepository::class);
});

test('it deactivates an active title at the current datetime by default', function () {
    $title = Title::factory()->active()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldReceive('pull')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime)),
            Mockery::any()
        )
        ->andReturn($title);

    resolve(DeactivateAction::class)->handle($title);
});

test('it deactivates an active title at a specific datetime', function () {
    $title = Title::factory()->active()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldReceive('pull')
        ->once()
        ->with(
            Mockery::on(fn ($t) => $t->id === $title->id),
            Mockery::on(fn ($d) => $d->eq($datetime)),
            Mockery::any()
        )
        ->andReturn($title);

    resolve(DeactivateAction::class)->handle($title, $datetime);
});

test('it throws exception for deactivating a non deactivatable title', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();

    resolve(DeactivateAction::class)->handle($title);
})->throws(CannotBePulledException::class)->with([
    'unactivated',
    'withFutureActivation',
    'inactive',
    'retired',
]);

test('title can be pulled when in correct state', function () {
    $activeTitle = Title::factory()->active()->create();
    $inactiveTitle = Title::factory()->inactive()->create();

    expect($activeTitle->canBeDeactivated())->toBeTrue();
    expect($inactiveTitle->canBeDeactivated())->toBeFalse();
});

test('title pull validation throws correct exceptions', function () {
    $activeTitle = Title::factory()->active()->create();
    $inactiveTitle = Title::factory()->inactive()->create();

    expect(fn () => $activeTitle->ensureCanBeDeactivated())->not->toThrow(Exception::class);
    expect(fn () => $inactiveTitle->ensureCanBeDeactivated())
        ->toThrow(CannotBeDeactivatedException::class);
});
