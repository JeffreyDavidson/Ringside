<?php

use App\Actions\Stables\RestoreAction;
use App\Models\Stable;
use App\Repositories\StableRepository;

use function Pest\Laravel\mock;

beforeEach(function () {
    $this->stableRepository = mock(StableRepository::class);
});

test('it restores a deleted stable', function () {
    $stable = Stable::factory()->trashed()->create();

    $this->stableRepository
        ->shouldReceive('restore')
        ->once()
        ->with($stable);

    RestoreAction::run($stable);
});
