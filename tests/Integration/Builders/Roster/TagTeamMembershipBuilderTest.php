<?php

declare(strict_types=1);

use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamWrestler;
use App\Models\Roster\Wrestlers\Wrestler;
use Illuminate\Support\Facades\Date;

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

test('tag team memberships can be filtered by overlapping periods', function (string $joinedAt, ?string $leftAt, bool $overlaps) {
    // Arrange
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $periodStart = Date::parse('2026-03-01 12:00:00');
    $periodEnd = Date::parse('2026-03-10 12:00:00');
    TagTeamWrestler::factory()->create([
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestler->id,
        'joined_at' => $joinedAt,
        'left_at' => $leftAt,
    ]);
    TagTeamWrestler::factory()->create([
        'joined_at' => $periodStart,
        'left_at' => null,
    ]);

    // Act
    $query = TagTeamWrestler::query();
    $query->forTagTeamId($tagTeam->id);
    $query->overlappingPeriod($periodStart, $periodEnd);
    $memberships = $query->pluck('wrestler_id');

    // Assert
    expect($memberships->all())->toBe($overlaps ? [$wrestler->id] : []);
})->with([
    'ends at start' => ['2026-02-01 12:00:00', '2026-03-01 12:00:00', true],
    'starts at end' => ['2026-03-10 12:00:00', '2026-03-20 12:00:00', true],
    'inside period' => ['2026-03-02 12:00:00', '2026-03-09 12:00:00', true],
    'spans period' => ['2026-02-01 12:00:00', '2026-04-01 12:00:00', true],
    'open before period' => ['2026-02-01 12:00:00', null, true],
    'ends one second before' => ['2026-02-01 12:00:00', '2026-03-01 11:59:59', false],
    'starts one second after' => ['2026-03-10 12:00:01', '2026-03-20 12:00:00', false],
    'open after period' => ['2026-03-10 12:00:01', null, false],
]);

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
