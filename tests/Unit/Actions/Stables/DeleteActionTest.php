<?php

declare(strict_types=1);

use App\Actions\Stables\DeleteAction;
use App\Models\Stables\Stable;
use App\Repositories\StableRepository;

beforeEach(function () {
    $this->stableRepository = $this->mock(StableRepository::class);
});

test('it deletes a stable', function () {
    $stable = Stable::factory()->create();

    $this->stableRepository
        ->shouldReceive('delete')
        ->once()
        ->with($stable);

    DeleteAction::run($stable);
    resolve(DeleteAction::class)->handle($stable);
});
