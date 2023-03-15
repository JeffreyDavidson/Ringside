<?php

use App\Actions\Referees\CreateAction;
use App\Data\RefereeData;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use function Pest\Laravel\mock;
use function Spatie\PestPluginTestTime\testTime;

beforeEach(function () {
    testTime()->freeze();

    $this->refereeRepository = mock(RefereeRepository::class);
});

test('it creates a referee', function () {
    $data = new RefereeData('Taylor', 'Otwell', null);

    $this->refereeRepository
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns(new Referee());

    CreateAction::run($data);
});

test('it employs a referee if start date is filled in request', function () {
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
