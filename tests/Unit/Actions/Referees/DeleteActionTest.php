<?php

declare(strict_types=1);

use App\Actions\Referees\DeleteAction;
use App\Models\Referee;
use App\Repositories\RefereeRepository;

beforeEach(function () {
    $this->refereeRepository = $this->mock(RefereeRepository::class);
});

test('it deletes a referee', function () {
    $referee = Referee::factory()->create();

    $this->refereeRepository
        ->shouldReceive('delete')
        ->once()
        ->with($referee);

    resolve(DeleteAction::class)->handle($data);
});
