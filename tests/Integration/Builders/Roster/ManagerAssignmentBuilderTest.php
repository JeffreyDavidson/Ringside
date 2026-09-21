<?php

declare(strict_types=1);

use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\TagTeams\TagTeamManager;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Roster\Wrestlers\WrestlerManager;

test('wrestler manager assignments can be queried by lifecycle state', function () {
    // Arrange
    $manager = Manager::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $currentHire = now()->subMonth();
    $recentHire = now()->subMonths(3);
    $oldestHire = now()->subMonths(5);

    WrestlerManager::query()->create([
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'hired_at' => $currentHire,
    ]);
    WrestlerManager::query()->create([
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'hired_at' => $oldestHire,
        'fired_at' => now()->subMonths(4),
    ]);
    WrestlerManager::query()->create([
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'hired_at' => $recentHire,
        'fired_at' => now()->subMonths(2),
    ]);

    // Act
    $currentQuery = WrestlerManager::query();
    $currentQuery->current();
    $current = $currentQuery->get();
    $endedQuery = WrestlerManager::query();
    $endedQuery->ended();
    $endedQuery->orderBy('hired_at');
    $ended = $endedQuery->get();
    $historyQuery = WrestlerManager::query();
    $historyQuery->forHistory();
    $history = $historyQuery->get();

    // Assert
    expect($current->pluck('hired_at')->map->toDateTimeString()->all())
        ->toBe([$currentHire->toDateTimeString()])
        ->and($ended->pluck('hired_at')->map->toDateTimeString()->all())
        ->toBe([$oldestHire->toDateTimeString(), $recentHire->toDateTimeString()])
        ->and($history->pluck('hired_at')->map->toDateTimeString()->all())
        ->toBe([$recentHire->toDateTimeString(), $oldestHire->toDateTimeString()]);
});

test('tag team manager assignments can be queried by lifecycle state', function () {
    // Arrange
    $manager = Manager::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $currentHire = now()->subMonth();
    $recentHire = now()->subMonths(3);
    $oldestHire = now()->subMonths(5);

    TagTeamManager::query()->create([
        'manager_id' => $manager->id,
        'tag_team_id' => $tagTeam->id,
        'hired_at' => $currentHire,
    ]);
    TagTeamManager::query()->create([
        'manager_id' => $manager->id,
        'tag_team_id' => $tagTeam->id,
        'hired_at' => $oldestHire,
        'fired_at' => now()->subMonths(4),
    ]);
    TagTeamManager::query()->create([
        'manager_id' => $manager->id,
        'tag_team_id' => $tagTeam->id,
        'hired_at' => $recentHire,
        'fired_at' => now()->subMonths(2),
    ]);

    // Act
    $currentQuery = TagTeamManager::query();
    $currentQuery->current();
    $current = $currentQuery->get();
    $endedQuery = TagTeamManager::query();
    $endedQuery->ended();
    $endedQuery->orderBy('hired_at');
    $ended = $endedQuery->get();
    $historyQuery = TagTeamManager::query();
    $historyQuery->forHistory();
    $history = $historyQuery->get();

    // Assert
    expect($current->pluck('hired_at')->map->toDateTimeString()->all())
        ->toBe([$currentHire->toDateTimeString()])
        ->and($ended->pluck('hired_at')->map->toDateTimeString()->all())
        ->toBe([$oldestHire->toDateTimeString(), $recentHire->toDateTimeString()])
        ->and($history->pluck('hired_at')->map->toDateTimeString()->all())
        ->toBe([$recentHire->toDateTimeString(), $oldestHire->toDateTimeString()]);
});

test('manager assignments can be queried by manager', function () {
    $manager = Manager::factory()->create();
    $otherManager = Manager::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();

    WrestlerManager::query()->create([
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'hired_at' => now(),
    ]);
    WrestlerManager::query()->create([
        'manager_id' => $otherManager->id,
        'wrestler_id' => $wrestler->id,
        'hired_at' => now(),
    ]);
    TagTeamManager::query()->create([
        'manager_id' => $manager->id,
        'tag_team_id' => $tagTeam->id,
        'hired_at' => now(),
    ]);
    TagTeamManager::query()->create([
        'manager_id' => $otherManager->id,
        'tag_team_id' => $tagTeam->id,
        'hired_at' => now(),
    ]);

    $wrestlerAssignments = WrestlerManager::query()
        ->forManagerId($manager->id)
        ->get();
    $tagTeamAssignments = TagTeamManager::query()
        ->forManagerId($manager->id)
        ->get();

    expect($wrestlerAssignments)->toHaveCount(1)
        ->and($wrestlerAssignments->firstOrFail()->manager_id)->toBe($manager->id)
        ->and($tagTeamAssignments)->toHaveCount(1)
        ->and($tagTeamAssignments->firstOrFail()->manager_id)->toBe($manager->id);
});

test('manager assignments can be queried by roster owner', function () {
    $manager = Manager::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $otherWrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $otherTagTeam = TagTeam::factory()->create();

    WrestlerManager::query()->create([
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'hired_at' => now(),
    ]);
    WrestlerManager::query()->create([
        'manager_id' => $manager->id,
        'wrestler_id' => $otherWrestler->id,
        'hired_at' => now(),
    ]);
    TagTeamManager::query()->create([
        'manager_id' => $manager->id,
        'tag_team_id' => $tagTeam->id,
        'hired_at' => now(),
    ]);
    TagTeamManager::query()->create([
        'manager_id' => $manager->id,
        'tag_team_id' => $otherTagTeam->id,
        'hired_at' => now(),
    ]);

    expect(WrestlerManager::query()->forWrestlerId($wrestler->id)->pluck('wrestler_id')->all())
        ->toBe([$wrestler->id])
        ->and(TagTeamManager::query()->forTagTeamId($tagTeam->id)->pluck('tag_team_id')->all())
        ->toBe([$tagTeam->id]);
});

test('manager assignments can be ordered by most recent hire', function () {
    $manager = Manager::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $oldestHiredAt = now()->subMonths(3);
    $newestHiredAt = now()->subMonth();
    $middleHiredAt = now()->subMonths(2);
    WrestlerManager::query()->create([
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'hired_at' => $oldestHiredAt,
    ]);
    WrestlerManager::query()->create([
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'hired_at' => $newestHiredAt,
    ]);
    WrestlerManager::query()->create([
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'hired_at' => $middleHiredAt,
    ]);

    $assignments = WrestlerManager::query()
        ->mostRecentlyHiredFirst()
        ->get();

    expect($assignments->pluck('hired_at')->map->toDateTimeString()->all())->toBe([
        $newestHiredAt->toDateTimeString(),
        $middleHiredAt->toDateTimeString(),
        $oldestHiredAt->toDateTimeString(),
    ]);
});
