<?php

use App\Actions\Wrestlers\EmployAction;
use App\Actions\Wrestlers\UnretireAction;
use App\Exceptions\CannotBeUnretiredException;
use App\Models\Wrestler;
use App\Repositories\WrestlerRepository;
use Illuminate\Support\Carbon;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;

test('it unretires a retired wrestler at the current datetime by default', function () {
    testTime()->freeze();
    $wrestler = Wrestler::factory()->retired()->create();
    $datetime = now();

    mock(WrestlerRepository::class)
        ->shouldReceive('unretire')
        ->once()
        ->withArgs(function (Wrestler $unretireWrestler, Carbon $unretireDate) use ($wrestler, $datetime) {
            $this->assertTrue($unretireWrestler->is($wrestler));
            $this->assertTrue($unretireDate->equalTo($datetime));

            return true;
        })
        ->andReturn($wrestler);

    EmployAction::shouldRun()
        ->withArgs(function (Wrestler $unretireWrestler, Carbon $employmentDate) use ($wrestler, $datetime) {
            $this->assertTrue($unretireWrestler->is($wrestler));
            $this->assertTrue($employmentDate->equalTo($datetime));

            return true;
        });

    UnretireAction::run($wrestler);
});

test('it unretires a retired wrestler at a specific datetime', function () {
    testTime()->freeze();
    $wrestler = Wrestler::factory()->retired()->create();
    $datetime = now()->addDays(2);

    mock(WrestlerRepository::class)
        ->shouldReceive('unretire')
        ->once()
        ->with($wrestler, $datetime)
        ->andReturn($wrestler);

    EmployAction::shouldRun()->with($wrestler, $datetime);

    UnretireAction::run($wrestler, $datetime);
});

test('invoke throws exception for unretiring a non unretirable wrestler', function ($factoryState) {
    $this->withoutExceptionHandling();

    $datetime = now();
    $wrestler = Wrestler::factory()->{$factoryState}()->create();

    UnretireAction::run($wrestler, $datetime);
})->throws(CannotBeUnretiredException::class)->with([
    'bookable',
    'withFutureEmployment',
    'injured',
    'released',
    'suspended',
    'unemployed',
]);
