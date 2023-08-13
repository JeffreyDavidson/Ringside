<?php

use App\Actions\EventMatches\AddTagTeamsToMatchAction;
use App\Models\EventMatch;
use App\Models\TagTeam;
use App\Repositories\EventMatchRepository;
use Database\Seeders\MatchTypesTableSeeder;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->eventMatchRepository = mock(EventMatchRepository::class);
});

test('it adds tag teams to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $tagTeams = TagTeam::factory()->count(1)->create();
    $sideNumber = 0;

    $this->eventMatchRepository
        ->shouldReceive('addTagTeamToMatch')
        ->with($eventMatch, \Mockery::type(TagTeam::class), $sideNumber)
        ->times($tagTeams->count());

    AddTagTeamsToMatchAction::run($eventMatch, $tagTeams, $sideNumber);
});
