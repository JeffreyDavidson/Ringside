<?php

declare(strict_types=1);

use App\Actions\Managers\EndCurrentRelationshipsAction;
use App\Models\Roster\Managers\Manager;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

test('it ends only the managers current relationships', function () {
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $tagTeam = TagTeam::factory()->employed()->create();
    $effectiveDate = now();
    $historicalHireDate = now()->subMonth();
    $historicalFireDate = now()->subWeek();
    $currentHireDate = now()->subDay();

    $manager->wrestlers()->attach($wrestler, [
        'hired_at' => $historicalHireDate,
        'fired_at' => $historicalFireDate,
    ]);
    $manager->wrestlers()->attach($wrestler, ['hired_at' => $currentHireDate]);
    $manager->tagTeams()->attach($tagTeam, ['hired_at' => $currentHireDate]);

    resolve(EndCurrentRelationshipsAction::class)
        ->handle($manager, $effectiveDate);

    $manager->refresh();

    expect($manager->currentWrestlers)->toBeEmpty()
        ->and($manager->currentTagTeams)->toBeEmpty();

    $this->assertDatabaseHas('wrestlers_managers', [
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'fired_at' => $historicalFireDate->toDateTimeString(),
    ]);
    $this->assertDatabaseHas('wrestlers_managers', [
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'hired_at' => $currentHireDate->toDateTimeString(),
        'fired_at' => $effectiveDate->toDateTimeString(),
    ]);
    $this->assertDatabaseHas('tag_teams_managers', [
        'manager_id' => $manager->id,
        'tag_team_id' => $tagTeam->id,
        'fired_at' => $effectiveDate->toDateTimeString(),
    ]);
});
