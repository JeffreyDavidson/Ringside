<?php

use App\Actions\Referees\DeleteAction;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->refereeRepository = mock(RefereeRepository::class);
});

test('it deletes a referee', function () {
    $referee = Referee::factory()->create();

    $this->refereeRepository
        ->shouldReceive('delete')
        ->once()
        ->with($referee);

    DeleteAction::run($referee);
});
