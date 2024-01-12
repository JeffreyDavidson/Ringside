<?php

declare(strict_types=1);

use App\Actions\Stables\RestoreAction;
use App\Models\Stable;
use App\Repositories\StableRepository;

beforeEach(function () {
    $this->stableRepository = Mockery::mock(StableRepository::class);
});

test('it restores a deleted stable', function () {
    $stable = Stable::factory()->trashed()->create();

    $this->stableRepository
        ->shouldReceive('restore')
        ->once()
        ->with($stable);

    RestoreAction::run($stable);
});
