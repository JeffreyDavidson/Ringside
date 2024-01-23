<?php

declare(strict_types=1);

use App\Actions\Titles\ActivateAction;
use App\Exceptions\CannotBeActivatedException;
use App\Models\Title;
use App\Repositories\TitleRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->titleRepository = Mockery::mock(TitleRepository::class);
});

test('it activates an activatable title at the current datetime by default', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldNotReceive('unretire');

    $this->titleRepository
        ->shouldReceive('activate')
        ->once()
        ->withArgs(function (Title $activatableTitle, Carbon $activationDate) use ($title, $datetime) {
            expect($activatableTitle->is($title))->toBeTrue()
                ->and($activationDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($title);

    ActivateAction::run($title);
})->with([
    'unactivated',
    'inactive',
    'withFutureActivation',
]);

test('it activates an activatable title at a specific datetime', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();
    $datetime = now()->addDays(2);

    $this->titleRepository
        ->shouldNotReceive('unretire');

    $this->titleRepository
        ->shouldReceive('activate')
        ->once()
        ->with($title, $datetime)
        ->andReturns($title);

    ActivateAction::run($title, $datetime);
})->with([
    'unactivated',
    'inactive',
    'withFutureActivation',
]);

test('it activates a retired title at the current datetime by default', function () {
    $title = Title::factory()->retired()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldReceive('unretire')
        ->withArgs(function (Title $unretirableTitle, Carbon $unretireDate) use ($title, $datetime) {
            expect($unretirableTitle->is($title))->toBeTrue()
                ->and($unretireDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->once()
        ->andReturn($title);

    $this->titleRepository
        ->shouldReceive('activate')
        ->once()
        ->withArgs(function (Title $activatedTitle, Carbon $activationDate) use ($title, $datetime) {
            expect($activatedTitle->is($title))->toBeTrue()
                ->and($activationDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($title);

    ActivateAction::run($title);
});

test('it activates a retired title at a specific datetime', function () {
    $title = Title::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->titleRepository
        ->shouldReceive('unretire')
        ->with($title, $datetime)
        ->once()
        ->andReturn($title);

    $this->titleRepository
        ->shouldReceive('activate')
        ->once()
        ->with($title, $datetime)
        ->andReturns($title);

    ActivateAction::run($title, $datetime);
});

test('it throws exception for activating a non activatable title', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();

    ActivateAction::run($title);
})->throws(CannotBeActivatedException::class)->with([
    'active',
]);
