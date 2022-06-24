<?php

use App\Actions\EventMatches\AddCompetitorsToMatchAction;
use App\Models\EventMatch;
use App\Models\TagTeam;
use App\Models\Wrestler;
use Database\Seeders\MatchTypesTableSeeder;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
});

test('add competitors to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $wrestlerA = Wrestler::factory()->create();
    $wrestlerB = Wrestler::factory()->create();
    $tagTeamA = TagTeam::factory()->create();
    $tagTeamB = TagTeam::factory()->create();
    $competitors = collect([
        0 => [
            'wrestlers' => collect([$wrestlerA]),
            'tag_teams' => collect([$tagTeamA]),
        ],
        1 => [
            'wrestlers' => collect([$wrestlerB]),
            'tag_teams' => collect([$tagTeamB]),
        ],
    ]);

    AddCompetitorsToMatchAction::run($eventMatch, $competitors);

    expect($eventMatch->competitors)->collectionHas($wrestlerA);
    expect($eventMatch->competitors)->collectionHas($wrestlerB);
    expect($eventMatch->competitors)->collectionHas($tagTeamA);
    expect($eventMatch->competitors)->collectionHas($tagTeamB);
});
