<?php

declare(strict_types=1);

use App\Actions\Wrestlers\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->wrestlerRepository = $this->mock(WrestlerRepository::class);
});

test('it employs an employable wrestler at the current datetime by default', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldNotReceive('unretire');

    $this->wrestlerRepository
        ->shouldReceive('employ')
        ->once()
        ->withArgs(function (Wrestler $employableWrestler, Carbon $employmentDate) use ($wrestler, $datetime) {
            expect($employableWrestler->is($wrestler))->toBeTrue()
                ->and($employmentDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($wrestler);

    EmployAction::run($wrestler);
})->with([
    'unemployed',
    'released',
    'withFutureEmployment',
])->skip();

test('it employs an employable wrestler at a specific datetime', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldNotReceive('unretire');

    $this->wrestlerRepository
        ->shouldReceive('employ')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    EmployAction::run($wrestler, $datetime);
})->with([
    'unemployed',
    'released',
    'withFutureEmployment',
])->skip();

test('it employs a retired wrestler at the current datetime by default', function () {
    $wrestler = Wrestler::factory()->retired()->create();
    $datetime = now();

    $this->wrestlerRepository
        ->shouldReceive('unretire')
        ->withArgs(function (Wrestler $unretirableWrestler, Carbon $unretireDate) use ($wrestler, $datetime) {
            expect($unretirableWrestler->is($wrestler))->toBeTrue()
                ->and($unretireDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->once()
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('employ')
        ->once()
        ->withArgs(function (Wrestler $employedWrestler, Carbon $employmentDate) use ($wrestler, $datetime) {
            expect($employedWrestler->is($wrestler))->toBeTrue()
                ->and($employmentDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($wrestler);

    EmployAction::run($wrestler);
});

test('it employs a retired wrestler at a specific datetime', function () {
    $wrestler = Wrestler::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->wrestlerRepository
        ->shouldReceive('unretire')
        ->with($wrestler, $datetime)
        ->once()
        ->andReturn($wrestler);

    $this->wrestlerRepository
        ->shouldReceive('employ')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturns($wrestler);

    EmployAction::run($wrestler, $datetime);
});

test('it throws exception for employing a non employable wrestler', function ($factoryState) {
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    EmployAction::run($wrestler);
})->throws(CannotBeEmployedException::class)->with([
    'suspended',
    'injured',
    'bookable',
]);
