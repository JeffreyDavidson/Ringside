<?php

declare(strict_types=1);

use App\Builders\Roster\StableMembershipBuilder;
use App\Models\Stables\Stable;
use App\Models\Stables\StableTagTeam;
use App\Models\Stables\StableWrestler;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

test('stable membership models use the stable membership builder', function () {
    expect(StableWrestler::query())->toBeInstanceOf(StableMembershipBuilder::class)
        ->and(StableTagTeam::query())->toBeInstanceOf(StableMembershipBuilder::class);
});

test('stable memberships can be filtered and ordered by their stable history', function () {
    $stable = Stable::factory()->create();
    $otherStable = Stable::factory()->create();
    $recentWrestler = Wrestler::factory()->create();
    $olderWrestler = Wrestler::factory()->create();
    $currentTagTeam = TagTeam::factory()->create();
    $formerTagTeam = TagTeam::factory()->create();

    StableWrestler::query()->create([
        'stable_id' => $stable->id,
        'wrestler_id' => $olderWrestler->id,
        'joined_at' => now()->subMonths(4),
        'left_at' => now()->subMonths(3),
    ]);
    StableWrestler::query()->create([
        'stable_id' => $stable->id,
        'wrestler_id' => $recentWrestler->id,
        'joined_at' => now()->subMonths(2),
        'left_at' => now()->subMonth(),
    ]);
    StableWrestler::query()->create([
        'stable_id' => $otherStable->id,
        'wrestler_id' => Wrestler::factory()->create()->id,
        'joined_at' => now()->subWeek(),
        'left_at' => now()->subDay(),
    ]);
    StableTagTeam::query()->create([
        'stable_id' => $stable->id,
        'tag_team_id' => $currentTagTeam->id,
        'joined_at' => now()->subMonth(),
    ]);
    StableTagTeam::query()->create([
        'stable_id' => $stable->id,
        'tag_team_id' => $formerTagTeam->id,
        'joined_at' => now()->subMonths(3),
        'left_at' => now()->subMonths(2),
    ]);

    $wrestlerIds = StableWrestler::query()
        ->forStableId($stable->id)
        ->ended()
        ->mostRecentlyJoinedFirst()
        ->pluck('wrestler_id')
        ->all();
    $formerTagTeamIds = StableTagTeam::query()
        ->forStableId($stable->id)
        ->ended()
        ->pluck('tag_team_id')
        ->all();

    expect($wrestlerIds)->toBe([$recentWrestler->id, $olderWrestler->id])
        ->and($formerTagTeamIds)->toBe([$formerTagTeam->id]);
});
