<?php

declare(strict_types=1);

use App\Actions\Stables\DeleteAction;
use App\Models\Stables\Stable;
use App\Repositories\StableRepository;
use Illuminate\Support\Collection;

beforeEach(function () {
    $this->stableRepository = $this->mock(StableRepository::class);
});

test('it deletes a stable', function () {
    $stable = $this->partialMock(Stable::class);
    $deletionDate = now();

    // Mock the stable to return empty collections for current members
    $stable->shouldReceive('hasDebuted')->andReturn(false);

    // Create empty collections for the relationships
    $emptyCollection = collect();

    $this->stableRepository
        ->shouldReceive('removeWrestlers')
        ->once()
        ->with($stable, Mockery::type(Collection::class), $deletionDate);

    $this->stableRepository
        ->shouldReceive('removeTagTeams')
        ->once()
        ->with($stable, Mockery::type(Collection::class), $deletionDate);

    // Note: removeManagers not called since managers are not direct stable members

    $this->stableRepository
        ->shouldReceive('delete')
        ->once()
        ->with($stable);

    resolve(DeleteAction::class)->handle($stable, $deletionDate);
});
