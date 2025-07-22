<?php

declare(strict_types=1);

use App\Actions\Titles\ActivateAction;
use App\Exceptions\CannotBeActivatedException;
use App\Models\Titles\Title;
use App\Repositories\TitleRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->titleRepository = $this->mock(TitleRepository::class);
});

test('it activates an activatable title at the current datetime by default', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldNotReceive('unretire');

    $this->titleRepository
        ->shouldReceive('createActivation')
        ->once()
        ->withArgs(function (Title $activatableTitle, Carbon $activationDate) use ($title, $datetime) {
            expect($activatableTitle->is($title))->toBeTrue()
                ->and($activationDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($title);

    resolve(ActivateAction::class)->handle($title);
})->with([
    'unactivated',
    'inactive',
    'withFutureActivation',
])->skip();

test('it activates an activatable title at a specific datetime', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();
    $datetime = now()->addDays(2);

    $this->titleRepository
        ->shouldNotReceive('unretire');

    $this->titleRepository
        ->shouldReceive('createActivation')
        ->once()
        ->with($title, $datetime)
        ->andReturns($title);

    resolve(ActivateAction::class)->handle($title, $datetime);
})->with([
    'unactivated',
    'inactive',
    'withFutureActivation',
])->skip();

test('it activates a retired title at the current datetime by default', function () {
    $title = Title::factory()->retired()->create();
    $datetime = now();

    $this->titleRepository
        ->shouldReceive('endRetirement')
        ->withArgs(function (Title $unretirableTitle, Carbon $unretireDate) use ($title, $datetime) {
            expect($unretirableTitle->is($title))->toBeTrue()
                ->and($unretireDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->once()
        ->andReturn($title);

    $this->titleRepository
        ->shouldReceive('createReinstatement')
        ->once()
        ->withArgs(function (Title $reinstatedTitle, Carbon $reinstateDate, $notes) use ($title, $datetime) {
            expect($reinstatedTitle->is($title))->toBeTrue()
                ->and($reinstateDate->eq($datetime))->toBeTrue()
                ->and($notes)->toBeNull();

            return true;
        })
        ->andReturns($title);

    resolve(ActivateAction::class)->handle($title);
});

test('it activates a retired title at a specific datetime', function () {
    $title = Title::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->titleRepository
        ->shouldReceive('endRetirement')
        ->with($title, $datetime)
        ->once()
        ->andReturn($title);

    $this->titleRepository
        ->shouldReceive('createReinstatement')
        ->once()
        ->with($title, $datetime, null)
        ->andReturns($title);

    resolve(ActivateAction::class)->handle($title, $datetime);
});

test('it throws exception for activating a non activatable title', function ($factoryState) {
    $title = Title::factory()->{$factoryState}()->create();

    resolve(ActivateAction::class)->handle($title);
})->throws(CannotBeActivatedException::class)->with([
    'active',
]);
