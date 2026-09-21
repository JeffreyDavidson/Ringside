<?php

declare(strict_types=1);

use App\Actions\Stables\AddStableMembersAction;
use App\Actions\Stables\SynchronizeStableMembersAction;
use App\Data\Stables\StableMembershipData;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Database\Eloquent\Collection;

it('synchronizes changed members while leaving omitted groups untouched', function () {
    $stable = Stable::factory()->create();
    $retainedWrestler = Wrestler::factory()->create();
    $removedWrestler = Wrestler::factory()->create();
    $addedWrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $addMembers = resolve(AddStableMembersAction::class);
    $synchronizeMembers = resolve(SynchronizeStableMembersAction::class);

    $addMembers->handle(
        $stable,
        new StableMembershipData(
            new Collection([$retainedWrestler, $removedWrestler]),
            new Collection([$tagTeam]),
        ),
        now()->subDay(),
    );

    $synchronizeMembers->handle(
        $stable,
        new StableMembershipData(wrestlers: new Collection([$retainedWrestler, $addedWrestler])),
        now(),
    );

    expect($stable->currentWrestlers()->pluck('wrestlers.id')->all())
        ->toEqualCanonicalizing([$retainedWrestler->id, $addedWrestler->id])
        ->and($stable->previousWrestlers()->whereKey($removedWrestler->id)->exists())->toBeTrue()
        ->and($stable->currentTagTeams()->whereKey($tagTeam->id)->exists())->toBeTrue();
});
