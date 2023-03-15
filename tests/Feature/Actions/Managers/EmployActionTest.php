<?php

use App\Actions\Managers\EmployAction;
use App\Exceptions\CannotBeEmployedException;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    Event::fake();

    testTime()->freeze();

    $this->managerRepository = mock(ManagerRepository::class);
});

test('it employs an employable manager at the current datetime by default', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldNotReceive('unretire');

    $this->managerRepository
        ->shouldReceive('employ')
        ->once()
        ->withArgs(function (Manager $employableManager, Carbon $employmentDate) use ($manager, $datetime) {
            expect($employableManager->is($manager))->toBeTrue();
            expect($employmentDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($manager);

    EmployAction::run($manager);
})->with([
    'unemployed',
    'released',
    'withFutureEmployment',
]);

test('it employs an employable manager at a specific datetime', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldNotReceive('unretire');

    $this->managerRepository
        ->shouldReceive('employ')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    EmployAction::run($manager, $datetime);
})->with([
    'unemployed',
    'released',
    'withFutureEmployment',
]);

test('it employs a retired manager at the current datetime by default', function () {
    $manager = Manager::factory()->retired()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('unretire')
        ->withArgs(function (Manager $unretirableManager, Carbon $unretireDate) use ($manager, $datetime) {
            expect($unretirableManager->is($manager))->toBeTrue();
            expect($unretireDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->once()
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('employ')
        ->once()
        ->withArgs(function (Manager $employedManager, Carbon $employmentDate) use ($manager, $datetime) {
            expect($employedManager->is($manager))->toBeTrue();
            expect($employmentDate->equalTo($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    EmployAction::run($manager);
});

test('it employs a retired manager at a specific datetime', function () {
    $manager = Manager::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('unretire')
        ->with($manager, $datetime)
        ->once()
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('employ')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    EmployAction::run($manager, $datetime);
});

test('invoke throws exception for employing a non employable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();

    EmployAction::run($manager);
})->throws(CannotBeEmployedException::class)->with([
    'suspended',
    'injured',
    'available',
]);
