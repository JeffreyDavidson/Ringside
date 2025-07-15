<?php

declare(strict_types=1);

use App\Actions\Managers\CreateAction;
use App\Data\Managers\ManagerData;
use App\Models\Managers\Manager;
use App\Repositories\ManagerRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->managerRepository = $this->mock(ManagerRepository::class);
});

test('it creates a manager', function () {
    $data = new ManagerData('Taylor', 'Otwell', null);

    $this->managerRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns(new Manager());

    $this->managerRepository
        ->shouldNotReceive('createEmployment');

    resolve(CreateAction::class)->handle($data);
});

test('an employment is created for the manager if start date is filled in request', function () {
    $datetime = now();
    $data = new ManagerData('Taylor', 'Otwell', $datetime);
    $manager = Manager::factory()->create(['first_name' => $data->first_name, 'last_name' => $data->last_name]);

    $this->managerRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturn($manager);

    $this->managerRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with($manager, $data->employment_date);

    resolve(CreateAction::class)->handle($data);
});
