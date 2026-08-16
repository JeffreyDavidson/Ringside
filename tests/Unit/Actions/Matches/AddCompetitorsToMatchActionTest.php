<?php

declare(strict_types=1);

use App\Actions\Matches\AddCompetitorsToMatchAction;
use App\Actions\Matches\AddTagTeamsToMatchAction;
use App\Actions\Matches\AddWrestlersToMatchAction;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use JMac\Testing\Double;
use JMac\Testing\Matching\Argument;

test('it adds wrestler competitors to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $wrestlerA = Wrestler::factory()->bookable()->create();
    $wrestlerB = Wrestler::factory()->bookable()->create();
    $competitors = collect([
        0 => [
            'wrestlers' => [$wrestlerA],
        ],
        1 => [
            'wrestlers' => [$wrestlerB],
        ],
    ]);

    $addWrestlersToMatchAction = Double::for(AddWrestlersToMatchAction::class);
    $addTagTeamsToMatchAction = Double::for(AddTagTeamsToMatchAction::class);

    app()->instance(AddWrestlersToMatchAction::class, $addWrestlersToMatchAction);
    app()->instance(AddTagTeamsToMatchAction::class, $addTagTeamsToMatchAction);

    $addWrestlersToMatchAction->expects('handle')->with($eventMatch, Argument::type('Illuminate\Support\Collection'), 0);
    $addWrestlersToMatchAction->expects('handle')->with($eventMatch, Argument::type('Illuminate\Support\Collection'), 1);

    resolve(AddCompetitorsToMatchAction::class)->handle($eventMatch, $competitors);

    $addWrestlersToMatchAction->verify();
    $addTagTeamsToMatchAction->unused();
});

test('it adds tag team competitors to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $tagTeamA = TagTeam::factory()->bookable()->create();
    $tagTeamB = TagTeam::factory()->bookable()->create();
    $competitors = collect([
        0 => [
            'tag_teams' => [$tagTeamA],
        ],
        1 => [
            'tag_teams' => [$tagTeamB],
        ],
    ]);

    $addWrestlersToMatchAction = Double::for(AddWrestlersToMatchAction::class);
    $addTagTeamsToMatchAction = Double::for(AddTagTeamsToMatchAction::class);

    app()->instance(AddWrestlersToMatchAction::class, $addWrestlersToMatchAction);
    app()->instance(AddTagTeamsToMatchAction::class, $addTagTeamsToMatchAction);

    $addTagTeamsToMatchAction->expects('handle')->with($eventMatch, Argument::type('Illuminate\Support\Collection'), 0);
    $addTagTeamsToMatchAction->expects('handle')->with($eventMatch, Argument::type('Illuminate\Support\Collection'), 1);

    resolve(AddCompetitorsToMatchAction::class)->handle($eventMatch, $competitors);

    $addTagTeamsToMatchAction->verify();
    $addWrestlersToMatchAction->unused();
});
