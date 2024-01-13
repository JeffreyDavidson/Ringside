<?php

declare(strict_types=1);

use App\Actions\Referees\CreateAction;
use App\Data\RefereeData;
use App\Models\Referee;
use App\Repositories\RefereeRepository;

use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->refereeRepository = Mockery::mock(RefereeRepository::class);
});

test('it creates a referee', function () {
    $data = new RefereeData('Taylor', 'Otwell', null);
    $referee = Referee::factory()->create(['first_name' => $data->first_name, 'last_name' => $data->last_name]);

    $this->refereeRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns($referee);

    CreateAction::run($data);
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
        ->shouldReceive('employ')
        ->once()
        ->with($referee, $data->start_date);

    CreateAction::run($data);
});
