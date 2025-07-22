<?php

declare(strict_types=1);

use App\Actions\Stables\UpdateAction;
use App\Data\Stables\StableData;
use App\Models\Stables\Stable;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\StableRepository;
use Illuminate\Database\Eloquent\Collection;

beforeEach(function () {
    $this->stableRepository = $this->mock(StableRepository::class);
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
        new Collection(),
        $newStableWrestlers,
    );

    $this->stableRepository
        ->shouldReceive('update')
        ->once()
        ->with($stable, $data)
        ->andReturns($stable);

    $this->stableRepository
        ->shouldReceive('updateStableMembers')
        ->once();

    $this->stableRepository
        ->shouldNotReceive('createActivation');

    resolve(UpdateAction::class)->handle($stable, $data);
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
        $newStableTagTeams,
        new Collection(),
    );

    $this->stableRepository
        ->shouldReceive('update')
        ->once()
        ->with($stable, $data)
        ->andReturns($stable);

    $this->stableRepository
        ->shouldReceive('updateStableMembers')
        ->once();

    $this->stableRepository
        ->shouldNotReceive('createActivation');

    resolve(UpdateAction::class)->handle($stable, $data);
});
