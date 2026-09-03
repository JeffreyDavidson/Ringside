<?php

declare(strict_types=1);

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;

test('tag team memberships can be filtered by tag team and wrestler', function () {
    $tagTeam = TagTeam::factory()->create();
    $otherTagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $otherWrestler = Wrestler::factory()->create();
    TagTeamWrestler::factory()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestler->id,
    ]);
    TagTeamWrestler::factory()->create([
        'tag_team_id' => $otherTagTeam->id,
        'wrestler_id' => $wrestler->id,
    ]);
    TagTeamWrestler::factory()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $otherWrestler->id,
    ]);

    $memberships = TagTeamWrestler::query()
        ->forTagTeamId($tagTeam->id)
        ->forWrestlerId($wrestler->id)
        ->get();

    expect($memberships)->toHaveCount(1)
        ->and($memberships->firstOrFail()->tag_team_id)->toBe($tagTeam->id)
        ->and($memberships->firstOrFail()->wrestler_id)->toBe($wrestler->id);
});

test('tag team memberships can exclude a wrestler', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $otherWrestler = Wrestler::factory()->create();
    TagTeamWrestler::factory()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestler->id,
    ]);
    TagTeamWrestler::factory()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $otherWrestler->id,
    ]);

    $memberships = TagTeamWrestler::query()
        ->forTagTeamId($tagTeam->id)
        ->excludingWrestlerId($wrestler->id)
        ->get();

    expect($memberships)->toHaveCount(1)
        ->and($memberships->firstOrFail()->wrestler_id)->toBe($otherWrestler->id);
});

test('tag team memberships can be filtered by overlapping periods', function () {
    $tagTeam = TagTeam::factory()->create();
    $overlappingWrestler = Wrestler::factory()->create();
    $earlierWrestler = Wrestler::factory()->create();
    $laterWrestler = Wrestler::factory()->create();
    TagTeamWrestler::factory()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $overlappingWrestler->id,
        'joined_at' => now()->subMonths(3),
        'left_at' => now()->subMonth(),
    ]);
    TagTeamWrestler::factory()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $earlierWrestler->id,
        'joined_at' => now()->subMonths(6),
        'left_at' => now()->subMonths(5),
    ]);
    TagTeamWrestler::factory()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $laterWrestler->id,
        'joined_at' => now(),
    ]);

    $memberships = TagTeamWrestler::query()
        ->forTagTeamId($tagTeam->id)
        ->overlappingPeriod(now()->subMonths(4), now()->subMonths(2))
        ->get();

    expect($memberships)->toHaveCount(1)
        ->and($memberships->firstOrFail()->wrestler_id)->toBe($overlappingWrestler->id);
});

test('tag team memberships can be ordered by most recent join', function () {
    $tagTeam = TagTeam::factory()->create();
    $oldestJoinedAt = now()->subMonths(3);
    $newestJoinedAt = now()->subMonth();
    $middleJoinedAt = now()->subMonths(2);

    foreach ([$oldestJoinedAt, $newestJoinedAt, $middleJoinedAt] as $joinedAt) {
        TagTeamWrestler::factory()->create([
            'tag_team_id' => $tagTeam->id,
            'joined_at' => $joinedAt,
        ]);
    }

    $memberships = TagTeamWrestler::query()
        ->mostRecentlyJoinedFirst()
        ->get();

    expect($memberships->pluck('joined_at')->map->toDateTimeString()->all())->toBe([
        $newestJoinedAt->toDateTimeString(),
        $middleJoinedAt->toDateTimeString(),
        $oldestJoinedAt->toDateTimeString(),
    ]);
});
