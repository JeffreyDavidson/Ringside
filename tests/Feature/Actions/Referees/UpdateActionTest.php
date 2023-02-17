<?php

use App\Actions\Referees\EmployAction;
use App\Actions\Referees\UpdateAction;
use App\Data\RefereeData;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Carbon;
use function Pest\Laravel\mock;

test('it can update a referee', function () {
    $data = new RefereeData('Taylor', 'Otwell', null);
    $referee = Referee::factory()->create();

    mock(RefereeRepository::class)
        ->shouldReceive('update')
        ->once()
        ->with($referee, $data)
        ->andReturns($referee);

    UpdateAction::run($referee, $data);

    EmployAction::shouldNotRun();
});

test('update can employ an unemployed referee when start date is filled', function () {
    $data = new RefereeData('Taylor', 'Otwell', Carbon::now());
    $referee = Referee::factory()->unemployed()->create();

    mock(RefereeRepository::class)
        ->shouldReceive('update')
        ->once()
        ->with($referee, $data)
        ->andReturns($referee);

    EmployAction::shouldRun();

    UpdateAction::run($referee, $data);
});

test('update can employ a future employed referee when start date is filled', function () {
    $data = new RefereeData('Taylor', 'Otwell', Carbon::now());
    $referee = Referee::factory()->withFutureEmployment()->create();

    mock(RefereeRepository::class)
        ->shouldReceive('update')
        ->once()
        ->with($referee, $data)
        ->andReturns($referee);

    EmployAction::shouldRun();

    UpdateAction::run($referee, $data);
});
