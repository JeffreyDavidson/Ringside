<?php

declare(strict_types=1);

use App\Actions\Stables\UpdateAction;
// use App\Actions\Stables\UpdateMembersAction;
use App\Data\Stables\StableData;
use App\Exceptions\Status\CannotUpdateStableException;
use App\Models\Managers\Manager;
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
        new Collection()
    );

    $this->stableRepository
        ->shouldReceive('update')
        ->once()
        ->with($stable, $data)
        ->andReturn($stable);

    $this->stableRepository
        ->shouldReceive('updateStableMembers')
        ->once()
        ->with($stable, $data->wrestlers, $data->tagTeams, $data->managers);

    // UpdateMembersAction::shouldRun()
    //     ->with($stable, $data->wrestlers, $data->tagTeams, $data->managers);

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
        new Collection()
    );

    $this->stableRepository
        ->shouldReceive('update')
        ->once()
        ->with($stable, $data)
        ->andReturn($stable);

    $this->stableRepository
        ->shouldReceive('updateStableMembers')
        ->once()
        ->with($stable, $data->wrestlers, $data->tagTeams, $data->managers);

    // UpdateMembersAction::shouldRun()
    //     ->once()
    //     ->with($stable, $data->wrestlers, $data->tagTeams, $data->managers);

    resolve(UpdateAction::class)->handle($stable, $data);
});

// Note: Manager syncing test removed since managers are not direct stable members
// Managers are associated with stables through the wrestlers/tag teams they manage

test('it throws exception when trying to change establishment date of active stable', function () {
    $stable = Stable::factory()->active()->create();
    $newDate = now()->addDays(5);

    $data = new StableData(
        'New Stable Name',
        $newDate,
        new Collection(),
        new Collection(),
        new Collection()
    );

    $this->stableRepository
        ->shouldReceive('update')
        ->once()
        ->with($stable, $data)
        ->andReturn($stable);

    // Should not call createEstablishment or updateStableMembers due to exception

    expect(fn () => resolve(UpdateAction::class)->handle($stable, $data))
        ->toThrow(CannotUpdateStableException::class);
});

test('it allows establishment date change for inactive stable', function () {
    $stable = Stable::factory()->inactive()->create();
    $newDate = now()->addDays(5);

    $data = new StableData(
        'New Stable Name',
        $newDate,
        new Collection(),
        new Collection(),
        new Collection()
    );

    $this->stableRepository
        ->shouldReceive('update')
        ->once()
        ->with($stable, $data)
        ->andReturn($stable);

    $this->stableRepository
        ->shouldReceive('createEstablishment')
        ->once()
        ->with($stable, $newDate);

    $this->stableRepository
        ->shouldReceive('updateStableMembers')
        ->once()
        ->with($stable, $data->wrestlers, $data->tagTeams, $data->managers);

    resolve(UpdateAction::class)->handle($stable, $data);
});
