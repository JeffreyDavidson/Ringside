<?php

use App\Actions\Referees\DeleteAction;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use function Pest\Laravel\mock;

test('handle deletes a referee', function () {
    $referee = Referee::factory()->create();

    mock(RefereeRepository::class)
        ->shouldReceive('delete')
        ->once()
        ->with($referee);

    DeleteAction::run($referee);
});
