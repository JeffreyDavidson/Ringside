<?php

use App\Actions\Referees\CreateAction;
use App\Actions\Referees\EmployAction;
use App\Data\RefereeData;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use Illuminate\Support\Carbon;
use function Pest\Laravel\mock;

test('store creates a referee and redirects', function () {
    $data = new RefereeData('Taylor', 'Otwell', null);

    mock(RefereeRepository::class)
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturns(new App\Models\Referee());

    CreateAction::run($data);
});

test('an employment is created for the referee if start date is filled in request', function () {
    $data = new RefereeData('Taylor', 'Otwell', Carbon::now());
    $referee = Referee::factory()->create(['first_name' => $data->first_name, 'last_name' => $data->last_name]);

    mock(RefereeRepository::class)
        ->shouldReceive('create')
        ->once()
        ->with($data)
        ->andReturn($referee);

    EmployAction::mock()->shouldReceive('handle')->with($referee, $data->start_date);

    CreateAction::run($data);
});
