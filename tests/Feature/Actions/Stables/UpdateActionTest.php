<?php

use App\Actions\Stables\UpdateAction;
use App\Data\StableData;
use App\Models\Stable;
use App\Models\TagTeam;
use App\Models\Wrestler;
use App\Repositories\StableRepository;

beforeEach(function () {
    $this->stableRepository = mock(StableRepository::class);
});

test('wrestlers of stable are synced when stable is updated', function () {
    $formerStableWrestlers = Wrestler::factory()->count(2)->create();
    $stable = Stable::factory()
        ->hasAttached($formerStableWrestlers, ['joined_at' => now()->toDateTimeString()])
        ->create();
    $newStableWrestlers = Wrestler::factory()->count(2)->create();

    $data = new StableData(
        'New Stable Name',
        null,
        collect(),
        $newStableWrestlers->modelKeys(),
        collect()
    );

    $this->stableRepository
        ->shouldReceive('update')
        ->once()
        ->with($stable, $data)
        ->andReturns($stable);

    $this->stableRepository
        ->shouldNotReceive('activate');

    UpdateAction::run($stable, $data);
});

test('tag teams of stable are synced when stable is updated', function () {
    $formerStableTagTeams = TagTeam::factory()->count(2)->create();
    $stable = Stable::factory()
        ->hasAttached($formerStableTagTeams, ['joined_at' => now()->toDateTimeString()])
        ->create();
    $newStableTagTeams = TagTeam::factory()->count(2)->create();

    $data = new StableData(
        'New Stable Name',
        null,
        $newStableTagTeams->modelKeys(),
        collect(),
        collect()
    );

    UpdateAction::run($stable, $data);
});
