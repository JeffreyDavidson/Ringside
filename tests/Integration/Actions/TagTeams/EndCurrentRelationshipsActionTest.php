<?php

declare(strict_types=1);

use App\Actions\TagTeams\EndCurrentRelationshipsAction;
use App\Models\Managers\Manager;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

test('it ends only the tag teams current relationships', function () {
    $tagTeam = TagTeam::factory()->employed()->create();
    $wrestler = Wrestler::factory()->employed()->create();
    $manager = Manager::factory()->employed()->create();
    $effectiveDate = now();
    $formerMembershipEndedAt = now()->subWeek();
    $currentMembershipStartedAt = now()->subDay();

    $tagTeam->wrestlers()->attach($wrestler, [
        'joined_at' => now()->subMonth(),
        'left_at' => $formerMembershipEndedAt,
    ]);
    $tagTeam->wrestlers()->attach($wrestler, ['joined_at' => $currentMembershipStartedAt]);
    $tagTeam->managers()->attach($manager, ['hired_at' => $currentMembershipStartedAt]);

    resolve(EndCurrentRelationshipsAction::class)
        ->handle($tagTeam, $effectiveDate);

    $tagTeam->refresh();

    expect($tagTeam->currentWrestlers)->toBeEmpty()
        ->and($tagTeam->currentManagers)->toBeEmpty();

    $this->assertDatabaseHas('tag_teams_wrestlers', [
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestler->id,
        'left_at' => $formerMembershipEndedAt->toDateTimeString(),
    ]);
    $this->assertDatabaseHas('tag_teams_wrestlers', [
        'tag_team_id' => $tagTeam->id,
        'wrestler_id' => $wrestler->id,
        'joined_at' => $currentMembershipStartedAt->toDateTimeString(),
        'left_at' => $effectiveDate->toDateTimeString(),
    ]);
    $this->assertDatabaseHas('tag_teams_managers', [
        'tag_team_id' => $tagTeam->id,
        'manager_id' => $manager->id,
        'fired_at' => $effectiveDate->toDateTimeString(),
    ]);
});
