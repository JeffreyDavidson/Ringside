<?php

declare(strict_types=1);

use App\Actions\Referees\CreateAction;
use App\Data\Referees\RefereeData;
use App\Models\Referees\Referee;
use App\Repositories\RefereeRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->refereeRepository = $this->mock(RefereeRepository::class);
});

test('it creates a referee', function () {
    $data = new RefereeData('Taylor', 'Otwell', null);
    $referee = Referee::factory()->create(['first_name' => $data->first_name, 'last_name' => $data->last_name]);

    $this->refereeRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns($referee);

    resolve(CreateAction::class)->handle($data);
});

test('it employs a referee if start date is provided', function () {
    $datetime = now();
    $data = new RefereeData('Hulk', 'Hogan', $datetime);
    $referee = Referee::factory()->create(['first_name' => $data->first_name, 'last_name' => $data->last_name]);

    $this->refereeRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturn($referee);

    $this->refereeRepository
        ->shouldReceive('createEmployment')
        ->once()
        ->with($referee, $data->employment_date);

    resolve(CreateAction::class)->handle($data);
});
