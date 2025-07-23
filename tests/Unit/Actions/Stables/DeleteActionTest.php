<?php

declare(strict_types=1);

use App\Actions\Stables\DeleteAction;
use App\Models\Stables\Stable;
use App\Repositories\StableRepository;

beforeEach(function () {
    $this->stableRepository = $this->mock(StableRepository::class);
});

test('it deletes a stable', function () {
    $stable = $this->mock(Stable::class);

    $stable->shouldReceive('hasDebuted')->andReturn(false);
    $stable->shouldReceive('getAttribute')->with('currentWrestlers')->andReturn(collect());
    $stable->shouldReceive('getAttribute')->with('currentTagTeams')->andReturn(collect());
    $stable->shouldReceive('setAttribute')->with('currentWrestlers', Mockery::any())->andReturn(null);
    $stable->shouldReceive('setAttribute')->with('currentTagTeams', Mockery::any())->andReturn(null);

    $this->stableRepository
        ->shouldReceive('removeWrestlers')
        ->once();

    $this->stableRepository
        ->shouldReceive('removeTagTeams')
        ->once();

    $this->stableRepository
        ->shouldReceive('delete')
        ->once()
        ->with($stable);

    resolve(DeleteAction::class)->handle($stable);
});
