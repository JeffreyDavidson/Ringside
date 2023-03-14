<?php

use App\Models\TagTeam;
use App\Models\Wrestler;

test('a wrestler belongs to many tag teams', function () {
    $wrestler = Wrestler::factory()->create();

    [$tagTeamA, $tagTeamB] = TagTeam::factory(2)
        ->bookable()
        ->sequence(['name' => 'Tag Team A'], ['name' => 'Tag Team B'])
        ->create();

    $wrestler->tagTeams()->attach($tagTeamA, ['joined_at' => now()]);

    $wrestler->refresh();

    expect($wrestler->tagTeams)->toHaveCount(1);
    expect($wrestler->previousTagTeams)->toHaveCount(0);
    expect($wrestler->currentTagTeam->is($tagTeamA))->toBeTrue();

    $wrestler->tagTeams()->updateExistingPivot($tagTeamA->id, ['left_at' => now()]);

    $wrestler->tagTeams()->attach($tagTeamB, ['joined_at' => now()]);

    $wrestler->refresh();

    expect($wrestler->tagTeams)->toHaveCount(2);
    expect($wrestler->currentTagTeam->is($tagTeamB))->toBeTrue();
    expect($wrestler->previousTagTeams)->toHaveCount(1);
    expect($wrestler->previousTagTeam->is($tagTeamA))->toBeTrue();
});
