<?php

declare(strict_types=1);

use App\Actions\Referees\RestoreAction;
use App\Models\Referees\Referee;
use App\Repositories\RefereeRepository;

beforeEach(function () {
    $this->refereeRepository = $this->mock(RefereeRepository::class);
});

test('it restores a deleted referee', function () {
    $referee = Referee::factory()->trashed()->create();

    $this->refereeRepository
        ->shouldReceive('restore')
        ->once()
        ->with($referee);

    resolve(RestoreAction::class)->handle($referee);
});
