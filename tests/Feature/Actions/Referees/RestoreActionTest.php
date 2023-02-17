<?php

use App\Actions\Referees\RestoreAction;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use function Pest\Laravel\mock;

test('handle restores a soft deleted referee', function () {
    $referee = Referee::factory()->trashed()->create();

    mock(RefereeRepository::class)
        ->shouldReceive('restore')
        ->once()
        ->with($referee);

    RestoreAction::run($referee);
});
