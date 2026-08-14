<?php

declare(strict_types=1);

use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\TagTeams\TagTeamManager;
use App\Models\Wrestlers\Wrestler;
use App\Models\Wrestlers\WrestlerManager;

test('wrestler manager assignments can be queried by lifecycle state', function () {
    $manager = Manager::factory()->create();
    $wrestler = Wrestler::factory()->create();

    WrestlerManager::query()->create([
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'hired_at' => now()->subMonth(),
    ]);
    WrestlerManager::query()->create([
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'hired_at' => now()->subMonths(3),
        'fired_at' => now()->subMonths(2),
    ]);

    expect(WrestlerManager::query()->current()->count())->toBe(1)
        ->and(WrestlerManager::query()->ended()->count())->toBe(1);
});

test('tag team manager assignments can be queried by lifecycle state', function () {
    $manager = Manager::factory()->create();
    $tagTeam = TagTeam::factory()->create();

    TagTeamManager::query()->create([
        'manager_id' => $manager->id,
        'tag_team_id' => $tagTeam->id,
        'hired_at' => now()->subMonth(),
    ]);
    TagTeamManager::query()->create([
        'manager_id' => $manager->id,
        'tag_team_id' => $tagTeam->id,
        'hired_at' => now()->subMonths(3),
        'fired_at' => now()->subMonths(2),
    ]);

    expect(TagTeamManager::query()->current()->count())->toBe(1)
        ->and(TagTeamManager::query()->ended()->count())->toBe(1);
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
