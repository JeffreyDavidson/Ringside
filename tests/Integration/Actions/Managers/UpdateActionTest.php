<?php

declare(strict_types=1);

use App\Actions\Managers\UpdateAction;
use App\Data\Managers\ManagerData;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;

beforeEach(function () {
    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it updates a manager', function () {
    $data = new ManagerData('Hulk', 'Hogan', null);
    $manager = Manager::factory()->create();

    $this->managerRepository
        ->shouldReceive('update')
        ->once()
        ->with($manager, $data)
        ->andReturns($manager);

    $this->managerRepository
        ->shouldNotReceive('createEmployment');

    resolve(UpdateAction::class)->handle($manager, $data);
});

test('it employs an employable manager if start date is filled in request', function () {
    $datetime = now();
    $data = new ManagerData('Hulk', 'Hogan', $datetime);
    $manager = Manager::factory()->create();

    $this->managerRepository
        ->shouldReceive('update')
        ->once()
        ->with($manager, $data)
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('createEmployment')
        ->with($manager, $data->employment_date)
        ->once()
        ->andReturn($manager);

    resolve(UpdateAction::class)->handle($manager, $data);
});

test('it updates a future employed manager employment date if start date is filled in request', function () {
    $datetime = now()->addDays(2);
    $data = new ManagerData('Hulk', 'Hogan', $datetime);
    $manager = Manager::factory()->withFutureEmployment()->create();

    $this->managerRepository
        ->shouldReceive('update')
        ->once()
        ->with($manager, $data)
        ->andReturns($manager);

    $this->managerRepository
        ->shouldReceive('createEmployment')
        ->with($manager, $data->employment_date)
        ->once()
        ->andReturn($manager);

    resolve(UpdateAction::class)->handle($manager, $data);
});
