<?php

use App\Actions\Titles\ActivateAction;
use App\Exceptions\CannotBeActivatedException;
use App\Models\Title;
use App\Repositories\TitleRepository;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->titleRepository = mock(TitleRepository::class);
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
            expect($activatableTitle->is($title))->toBeTrue();
            expect($activationDate->equalTo($datetime))->toBeTrue();

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
            expect($unretirableTitle->is($title))->toBeTrue();
            expect($unretireDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->once()
        ->andReturn($title);

    $this->titleRepository
        ->shouldReceive('activate')
        ->once()
        ->withArgs(function (Title $activatedTitle, Carbon $activationDate) use ($title, $datetime) {
            expect($activatedTitle->is($title))->toBeTrue();
            expect($activationDate->equalTo($datetime))->toBeTrue();

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

test('invoke throws exception for activating a non activatable title', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();

    ActivateAction::run($title);
})->throws(CannotBeActivatedException::class)->with([
    'active',
]);
