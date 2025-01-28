<?php

declare(strict_types=1);

use App\Actions\Stables\DeleteAction;
use App\Models\Stable;
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

    app(DeleteAction::class)->handle($stable);
});
