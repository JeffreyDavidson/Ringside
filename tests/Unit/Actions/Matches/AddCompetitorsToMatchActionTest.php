<?php

declare(strict_types=1);

use App\Actions\Matches\AddCompetitorsToMatchAction;
use App\Actions\Matches\AddTagTeamsToMatchAction;
use App\Actions\Matches\AddWrestlersToMatchAction;
use App\Models\Matches\EventMatch;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use Database\Seeders\MatchTypesTableSeeder;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
});

test('it adds wrestler competitors to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->count(2)->create();
    $competitors = collect([
        0 => [
            'wrestlers' => collect([$wrestlerA]),
        ],
        1 => [
            'wrestlers' => collect([$wrestlerB]),
        ],
    ]);

    AddWrestlersToMatchAction::shouldRun()
        ->with($eventMatch, $competitors[0]['wrestlers'], 0)
        ->once();

    AddWrestlersToMatchAction::shouldRun()
        ->with($eventMatch, $competitors[1]['wrestlers'], 1)
        ->once();

    AddTagTeamsToMatchAction::shouldNotRun();

    AddCompetitorsToMatchAction::run($eventMatch, $competitors);
});

test('it adds tag team competitors to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    [$tagTeamA, $tagTeamB] = TagTeam::factory()->count(2)->create();
    $competitors = collect([
        0 => [
            'tag_teams' => collect([$tagTeamA]),
        ],
        1 => [
            'tag_teams' => collect([$tagTeamB]),
        ],
    ]);

    AddTagTeamsToMatchAction::shouldRun()
        ->with($eventMatch, $competitors[0]['tag_teams'], 0)
        ->once();

    AddTagTeamsToMatchAction::shouldRun()
        ->with($eventMatch, $competitors[1]['tag_teams'], 1)
        ->once();

    AddWrestlersToMatchAction::shouldNotRun();

    AddCompetitorsToMatchAction::run($eventMatch, $competitors);
});
