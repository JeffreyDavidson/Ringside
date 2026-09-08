<?php

declare(strict_types=1);

use App\Builders\Roster\StableMembershipBuilder;
use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('stable membership models use the stable membership builder', function () {
    expect(StableWrestler::query())->toBeInstanceOf(StableMembershipBuilder::class)
        ->and(StableTagTeam::query())->toBeInstanceOf(StableMembershipBuilder::class);
});

test('stable memberships can be filtered and ordered by their stable history', function () {
    // Arrange
    $stable = Stable::factory()->create();
    $otherStable = Stable::factory()->create();
    $recentWrestler = Wrestler::factory()->create();
    $olderWrestler = Wrestler::factory()->create();
    $currentTagTeam = TagTeam::factory()->create();
    $formerTagTeam = TagTeam::factory()->create();
    $olderTagTeam = TagTeam::factory()->create();

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
    StableWrestler::query()->create([
        'stable_id' => $stable->id,
        'wrestler_id' => Wrestler::factory()->create()->id,
        'joined_at' => now()->subWeek(),
    ]);
    StableTagTeam::query()->create([
        'stable_id' => $stable->id,
        'tag_team_id' => $olderTagTeam->id,
        'joined_at' => now()->subMonths(5),
        'left_at' => now()->subMonths(4),
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

    StableTagTeam::query()->create([
        'stable_id' => $otherStable->id,
        'tag_team_id' => TagTeam::factory()->create()->id,
        'joined_at' => now()->subWeek(),
        'left_at' => now()->subDay(),
    ]);

    // Act
    $wrestlerQuery = StableWrestler::query();
    $wrestlerQuery->forStableId($stable->id);
    $wrestlerQuery->forHistory();
    $wrestlerIds = $wrestlerQuery->pluck('wrestler_id');
    $tagTeamQuery = StableTagTeam::query();
    $tagTeamQuery->forStableId($stable->id);
    $tagTeamQuery->forHistory();
    $formerTagTeamIds = $tagTeamQuery->pluck('tag_team_id');

    // Assert
    expect($wrestlerIds->all())->toBe([$recentWrestler->id, $olderWrestler->id])
        ->and($formerTagTeamIds->all())->toBe([$formerTagTeam->id, $olderTagTeam->id]);
});
