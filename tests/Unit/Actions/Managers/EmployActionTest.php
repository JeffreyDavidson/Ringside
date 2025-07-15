<?php

declare(strict_types=1);

use App\Actions\Managers\EmployAction;
use App\Exceptions\Status\CannotBeEmployedException;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it employs an employable manager at the current datetime by default', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldNotReceive('unretire');

    $this->managerRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->withArgs(function (Manager $employableManager, Carbon $employmentDate) use ($manager, $datetime) {
            expect($employableManager->is($manager))->toBeTrue()
                ->and($employmentDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturn($manager);

    resolve(EmployAction::class)->handle($manager);
})->with([
    'unemployed',
    'released',
    'withFutureEmployment',
])->skip();

test('it employs an employable manager at a specific datetime', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldNotReceive('unretire');

    $this->managerRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    resolve(EmployAction::class)->handle($manager, $datetime);
})->with([
    'unemployed',
    'released',
    'withFutureEmployment',
])->skip();

test('it employs a retired manager at the current datetime by default', function () {
    $manager = Manager::factory()->retired()->create();
    $datetime = now();

    $this->managerRepository
        ->shouldReceive('endRetirement')
        ->withArgs(function (Manager $retiredManager, Carbon $endDate) use ($manager, $datetime) {
            expect($retiredManager->is($manager))->toBeTrue()
                ->and($endDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->once();

    $this->managerRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->withArgs(function (Manager $employedManager, Carbon $employmentDate) use ($manager, $datetime) {
            expect($employedManager->is($manager))->toBeTrue()
                ->and($employmentDate->eq($datetime))->toBeTrue();

            return true;
        })
        ->andReturns($manager);

    resolve(EmployAction::class)->handle($manager);
});

test('it employs a retired manager at a specific datetime', function () {
    $manager = Manager::factory()->retired()->create();
    $datetime = now()->addDays(2);

    $this->managerRepository
        ->shouldReceive('endRetirement')
        ->with($manager, $datetime)
        ->once();

    $this->managerRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with($manager, $datetime)
        ->andReturns($manager);

    resolve(EmployAction::class)->handle($manager, $datetime);
});

test('it throws exception for employing a non employable manager', function ($factoryState) {
    $manager = Manager::factory()->{$factoryState}()->create();

    resolve(EmployAction::class)->handle($manager);
})->throws(CannotBeEmployedException::class)->with([
    'suspended',
    'injured',
    'available',
]);
