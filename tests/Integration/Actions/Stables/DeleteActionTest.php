<?php

use App\Actions\Stables\DeleteAction;
use App\Models\Stable;
use App\Repositories\StableRepository;

use function Pest\Laravel\mock;

beforeEach(function () {
    $this->stableRepository = mock(StableRepository::class);
});

test('it deletes a stable', function () {
    $stable = Stable::factory()->create();

    $this->stableRepository
        ->shouldReceive('delete')
        ->once()
        ->with($stable);

    DeleteAction::run($stable);
});
