<?php

use App\Actions\Referees\RestoreAction;
use App\Models\Referee;
use App\Repositories\RefereeRepository;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->refereeRepository = mock(RefereeRepository::class);
});

test('it restores a deleted referee', function () {
    $referee = Referee::factory()->trashed()->create();

    $this->refereeRepository
        ->shouldReceive('restore')
        ->once()
        ->with($referee);

    RestoreAction::run($referee);
});
