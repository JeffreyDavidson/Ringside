<?php

declare(strict_types=1);

use App\Actions\Managers\EndCurrentRelationshipsAction;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

test('it ends only the managers current relationships', function () {
    $manager = Manager::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $tagTeam = TagTeam::factory()->employed()->create();
    $effectiveDate = now();

    $manager->wrestlers()->attach($wrestler, [
        'hired_at' => now()->subMonth(),
        'fired_at' => now()->subWeek(),
    ]);
    $manager->wrestlers()->attach($wrestler, ['hired_at' => now()->subDay()]);
    $manager->tagTeams()->attach($tagTeam, ['hired_at' => now()->subDay()]);

    resolve(EndCurrentRelationshipsAction::class)
        ->handle($manager, $effectiveDate);

    $manager->refresh();

    expect($manager->currentWrestlers)->toBeEmpty()
        ->and($manager->currentTagTeams)->toBeEmpty();

    $this->assertDatabaseHas('wrestlers_managers', [
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'fired_at' => now()->subWeek()->toDateTimeString(),
    ]);
    $this->assertDatabaseHas('wrestlers_managers', [
        'manager_id' => $manager->id,
        'wrestler_id' => $wrestler->id,
        'hired_at' => now()->subDay()->toDateTimeString(),
        'fired_at' => $effectiveDate->toDateTimeString(),
    ]);
    $this->assertDatabaseHas('tag_teams_managers', [
        'manager_id' => $manager->id,
        'tag_team_id' => $tagTeam->id,
        'fired_at' => $effectiveDate->toDateTimeString(),
    ]);
});
