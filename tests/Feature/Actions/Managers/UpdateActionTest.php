<?php

use App\Actions\Managers\EmployAction;
use App\Actions\Managers\UpdateAction;
use App\Data\ManagerData;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;
use function Pest\Laravel\mock;

test('it can update a manager', function () {
    $data = new ManagerData('Taylor', 'Otwell', null);
    $manager = Manager::factory()->create();

    mock(ManagerRepository::class)
        ->shouldReceive('update')
        ->once()
        ->with($manager, $data)
        ->andReturns($manager);

    UpdateAction::run($manager, $data);

    EmployAction::shouldNotRun();
});

test('it can employ an unemployed manager when start date is filled', function () {
    $data = new ManagerData('Taylor', 'Otwell', Carbon::now());
    $manager = Manager::factory()->unemployed()->create();

    mock(ManagerRepository::class)
        ->shouldReceive('update')
        ->once()
        ->with($manager, $data)
        ->andReturns($manager);

    EmployAction::shouldRun();

    UpdateAction::run($manager, $data);
});

test('it can employ a future employed manager when start date is filled', function () {
    $data = new ManagerData('Taylor', 'Otwell', Carbon::now());
    $manager = Manager::factory()->withFutureEmployment()->create();

    mock(ManagerRepository::class)
        ->shouldReceive('update')
        ->once()
        ->with($manager, $data)
        ->andReturns($manager);

    EmployAction::shouldRun();

    UpdateAction::run($manager, $data);
});
