<?php

declare(strict_types=1);

use App\Actions\TagTeams\EndCurrentRelationshipsAction;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

test('it ends only the tag teams current relationships', function () {
    $tagTeam = TagTeam::factory()->create();
    $wrestler = Wrestler::factory()->create();
    $manager = Manager::factory()->create();
    $effectiveDate = now();

    $tagTeam->wrestlers()->attach($wrestler, [
        'joined_at' => now()->subMonth(),
        'left_at' => now()->subWeek(),
    ]);
    $tagTeam->wrestlers()->attach($wrestler, ['joined_at' => now()->subDay()]);
    $tagTeam->managers()->attach($manager, ['hired_at' => now()->subDay()]);

    resolve(EndCurrentRelationshipsAction::class)
        ->handle($tagTeam, $effectiveDate);

    $tagTeam->refresh();

    expect($tagTeam->currentWrestlers)->toBeEmpty()
        ->and($tagTeam->currentManagers)->toBeEmpty();

    $this->assertDatabaseHas('tag_teams_wrestlers', [
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestler->id,
        'left_at' => now()->subWeek()->toDateTimeString(),
    ]);
});
