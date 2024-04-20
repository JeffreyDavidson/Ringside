<?php

declare(strict_types=1);

use App\Actions\Referees\UpdateAction;
use App\Data\RefereeData;
use App\Models\Referee;
use App\Repositories\RefereeRepository;

beforeEach(function () {
    $this->refereeRepository = $this->mock(RefereeRepository::class);
});

test('it can update a referee', function () {
    $data = new RefereeData('Taylor', 'Otwell', null);
    $referee = Referee::factory()->create();

    $this->refereeRepository
        ->shouldReceive('update')
        ->once()
        ->with($referee, $data)
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldNotReceive('employ');

    UpdateAction::run($referee, $data);
});

test('it employs an employable referee if start date is filled in request', function () {
    $datetime = now();
    $data = new RefereeData('Taylor', 'Otwell', $datetime);
    $referee = Referee::factory()->create();

    $this->refereeRepository
        ->shouldReceive('update')
        ->once()
        ->with($referee, $data)
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('employ')
        ->with($referee, $data->start_date)
        ->once()
        ->andReturn($referee);

    UpdateAction::run($referee, $data);
});

test('it updates a future employed referee employment date if start date is filled in request', function () {
    $datetime = now()->addDays(2);
    $data = new RefereeData('Taylor', 'Otwell', $datetime);
    $referee = Referee::factory()->withFutureEmployment()->create();

    $this->refereeRepository
        ->shouldReceive('update')
        ->once()
        ->with($referee, $data)
        ->andReturns($referee);

    $this->refereeRepository
        ->shouldReceive('employ')
        ->with($referee, $data->start_date)
        ->once()
        ->andReturn($referee);

    UpdateAction::run($referee, $data);
});
