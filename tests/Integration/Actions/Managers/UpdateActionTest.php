<?php

declare(strict_types=1);

use App\Actions\Managers\UpdateAction;
use App\Data\ManagerData;
use App\Models\Manager;
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
        ->shouldNotReceive('employ');

    UpdateAction::run($manager, $data);
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
        ->shouldReceive('employ')
        ->with($manager, $data->start_date)
        ->once()
        ->andReturn($manager);

    UpdateAction::run($manager, $data);
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
        ->shouldReceive('employ')
        ->with($manager, $data->start_date)
        ->once()
        ->andReturn($manager);

    UpdateAction::run($manager, $data);
});
