<?php

use App\Actions\Wrestlers\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;

test('it employs an unemployed wrestler at the current datetime by default', function () {
    testTime()->freeze();
    $wrestler = Wrestler::factory()->unemployed()->create();
    $datetime = now();

    mock(WrestlerRepository::class)
        ->shouldReceive('employ')
        ->once()
        ->withArgs(function (Wrestler $unretireWrestler, Carbon $employmentDate) use ($wrestler, $datetime) {
            $this->assertTrue($unretireWrestler->is($wrestler));
            $this->assertTrue($employmentDate->equalTo($datetime));

            return true;
        })
        ->andReturn($wrestler);

    EmployAction::run($wrestler);
});

test('it employs an unemployed wrestler at a specific datetime', function () {
    testTime()->freeze();
    $wrestler = Wrestler::factory()->unemployed()->create();
    $datetime = now()->addDays(2);

    mock(WrestlerRepository::class)
        ->shouldReceive('employ')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    EmployAction::run($wrestler, $datetime);
});

test('it employs an unemployed wrestler', function () {
    $wrestler = Wrestler::factory()->unemployed()->create();
    $datetime = now();

    mock(WrestlerRepository::class)
        ->shouldReceive('employ')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    EmployAction::run($wrestler, $datetime);
});

test('it employs a future employed wrestler', function () {
    $wrestler = Wrestler::factory()->withFutureEmployment()->create();
    $datetime = now();

    mock(WrestlerRepository::class)
        ->shouldReceive('employ')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    EmployAction::run($wrestler, $datetime);
});

test('it employs a released wrestler', function () {
    $wrestler = Wrestler::factory()->released()->create();
    $datetime = now();

    mock(WrestlerRepository::class)
        ->shouldReceive('employ')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    EmployAction::run($wrestler, $datetime);
});

test('it employs a retired wrestler', function () {
    $wrestler = Wrestler::factory()->retired()->create();
    $datetime = now();

    mock(WrestlerRepository::class)
        ->shouldReceive('employ')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    EmployAction::run($wrestler, $datetime);
});

test('invoke throws exception for employing a non employable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    EmployAction::run($wrestler);
})->throws(CannotBeEmployedException::class)->with([
    'suspended',
    'injured',
    'bookable',
]);
