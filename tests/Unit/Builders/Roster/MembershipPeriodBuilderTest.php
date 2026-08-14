<?php

declare(strict_types=1);

use App\Models\Stables\Stable;
use App\Models\Stables\StableTagTeam;
use App\Models\Stables\StableWrestler;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamWrestler;
use App\Models\Wrestlers\Wrestler;

test('membership periods can be queried by lifecycle state', function () {
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

    expect(TagTeamWrestler::query()->current()->count())->toBe(1)
        ->and(TagTeamWrestler::query()->ended()->count())->toBe(1)
        ->and(StableWrestler::query()->current()->count())->toBe(1)
        ->and(StableWrestler::query()->ended()->count())->toBe(1)
        ->and(StableTagTeam::query()->current()->count())->toBe(1)
        ->and(StableTagTeam::query()->ended()->count())->toBe(1);
});
