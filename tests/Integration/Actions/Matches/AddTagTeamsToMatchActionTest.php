<?php

declare(strict_types=1);

use App\Actions\Matches\AddTagTeamsToMatchAction;
use App\Models\Matches\EventMatch;
use App\Models\TagTeams\TagTeam;
use App\Repositories\EventMatchRepository;
use Database\Seeders\MatchTypesTableSeeder;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->eventMatchRepository = $this->mock(EventMatchRepository::class);
});

test('it adds tag teams to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $tagTeams = TagTeam::factory()->bookable()->count(1)->create();
    $sideNumber = 1;

    $this->eventMatchRepository
        ->shouldReceive('addTagTeamToMatch')
        ->with($eventMatch, Mockery::type(TagTeam::class), $sideNumber)
        ->times($tagTeams->count());

    AddTagTeamsToMatchAction::run($eventMatch, $tagTeams, $sideNumber);
});
