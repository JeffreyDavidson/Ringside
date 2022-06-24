<?php

use App\Actions\EventMatches\AddTagTeamsToMatchAction;
use App\Models\EventMatch;
use App\Models\TagTeam;
use Database\Seeders\MatchTypesTableSeeder;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
});

test('add tag teams to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $tagTeams = TagTeam::factory()->count(1)->create();
    $sideNumber = 0;

    AddTagTeamsToMatchAction::run($eventMatch, $tagTeams, $sideNumber);

    expect($eventMatch->tagTeams)->collectionHas($tagTeams->first());
});
