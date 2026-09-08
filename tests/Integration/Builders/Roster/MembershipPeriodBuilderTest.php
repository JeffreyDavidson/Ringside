<?php

declare(strict_types=1);

use App\Models\Roster\Stables\Stable;
use App\Models\Roster\Stables\StableTagTeam;
use App\Models\Roster\Stables\StableWrestler;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;

test('membership periods can be queried by lifecycle state', function () {
    // Arrange
    $stable = Stable::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $currentWrestler = Wrestler::factory()->create();
    $formerWrestler = Wrestler::factory()->create();

    TagTeamWrestler::query()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $currentWrestler->id,
        'joined_at' => now()->subMonth(),
    ]);
    TagTeamWrestler::query()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $formerWrestler->id,
        'joined_at' => now()->subMonths(3),
        'left_at' => now()->subMonths(2),
    ]);

    StableWrestler::query()->create([
        'stable_id' => $stable->id,
        'wrestler_id' => $currentWrestler->id,
        'joined_at' => now()->subMonth(),
    ]);
    StableWrestler::query()->create([
        'stable_id' => $stable->id,
        'wrestler_id' => $formerWrestler->id,
        'joined_at' => now()->subMonths(3),
        'left_at' => now()->subMonths(2),
    ]);

    $formerTagTeam = TagTeam::factory()->create();
    StableTagTeam::query()->create([
        'stable_id' => $stable->id,
        'tag_team_id' => $tagTeam->id,
        'joined_at' => now()->subMonth(),
    ]);
    StableTagTeam::query()->create([
        'stable_id' => $stable->id,
        'tag_team_id' => $formerTagTeam->id,
        'joined_at' => now()->subMonths(3),
        'left_at' => now()->subMonths(2),
    ]);

    // Act
    $currentTagTeamQuery = TagTeamWrestler::query();
    $currentTagTeamQuery->current();
    $currentTagTeamMembers = $currentTagTeamQuery->pluck('wrestler_id');
    $endedTagTeamQuery = TagTeamWrestler::query();
    $endedTagTeamQuery->ended();
    $formerTagTeamMembers = $endedTagTeamQuery->pluck('wrestler_id');

    $currentStableWrestlerQuery = StableWrestler::query();
    $currentStableWrestlerQuery->current();
    $currentStableWrestlers = $currentStableWrestlerQuery->pluck('wrestler_id');
    $endedStableWrestlerQuery = StableWrestler::query();
    $endedStableWrestlerQuery->ended();
    $formerStableWrestlers = $endedStableWrestlerQuery->pluck('wrestler_id');

    $currentStableTagTeamQuery = StableTagTeam::query();
    $currentStableTagTeamQuery->current();
    $currentStableTagTeams = $currentStableTagTeamQuery->pluck('tag_team_id');
    $endedStableTagTeamQuery = StableTagTeam::query();
    $endedStableTagTeamQuery->ended();
    $formerStableTagTeams = $endedStableTagTeamQuery->pluck('tag_team_id');

    // Assert
    expect($currentTagTeamMembers->all())->toBe([$currentWrestler->id])
        ->and($formerTagTeamMembers->all())->toBe([$formerWrestler->id])
        ->and($currentStableWrestlers->all())->toBe([$currentWrestler->id])
        ->and($formerStableWrestlers->all())->toBe([$formerWrestler->id])
        ->and($currentStableTagTeams->all())->toBe([$tagTeam->id])
        ->and($formerStableTagTeams->all())->toBe([$formerTagTeam->id]);
});
