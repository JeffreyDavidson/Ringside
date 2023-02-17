<?php

use App\Actions\Managers\CreateAction;
use App\Actions\Managers\EmployAction;
use App\Data\ManagerData;
use App\Models\Manager;
use App\Repositories\ManagerRepository;
use Illuminate\Support\Carbon;
use function Pest\Laravel\mock;

test('store creates a manager and redirects', function () {
    $data = new ManagerData('Taylor', 'Otwell', null);

    mock(ManagerRepository::class)
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns(new App\Models\Manager());

    CreateAction::run($data);
});

test('an employment is created for the manager if start date is filled in request', function () {
    $data = new ManagerData('Taylor', 'Otwell', Carbon::now());
    $manager = Manager::factory()->create(['first_name' => $data->first_name, 'last_name' => $data->last_name]);

    mock(ManagerRepository::class)
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturn($manager);

    EmployAction::mock()->shouldReceive('handle')->with($manager, $data->start_date);

    CreateAction::run($data);
});
