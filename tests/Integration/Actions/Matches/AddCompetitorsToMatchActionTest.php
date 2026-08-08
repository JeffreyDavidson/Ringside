<?php

declare(strict_types=1);

use App\Actions\Matches\AddCompetitorsToMatchAction;
use App\Actions\Matches\AddTagTeamsToMatchAction;
use App\Actions\Matches\AddWrestlersToMatchAction;
use App\Models\Matches\EventMatch;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;
use JMac\Testing\Double;

test('it adds wrestler competitors to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $competitors = collect([
        0 => [
            'wrestlers' => [$wrestlerA],
        ],
        1 => [
            'wrestlers' => [$wrestlerB],
        ],
    ]);

    $addWrestlersAction = Double::for(AddWrestlersToMatchAction::class);
    $addTagTeamsAction = Double::for(AddTagTeamsToMatchAction::class);
    $addWrestlersAction->expects('handle')->times(2);

    $this->app->instance(AddWrestlersToMatchAction::class, $addWrestlersAction);
    $this->app->instance(AddTagTeamsToMatchAction::class, $addTagTeamsAction);

    AddCompetitorsToMatchAction::run($eventMatch, $competitors);

    $addWrestlersAction->verify();
    $addTagTeamsAction->unused();
});

test('it adds tag team competitors to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    [$tagTeamA, $tagTeamB] = TagTeam::factory()->bookable()->count(2)->create();
    $competitors = collect([
        0 => [
            'tag_teams' => [$tagTeamA],
        ],
        1 => [
            'tag_teams' => [$tagTeamB],
        ],
    ]);

    $addWrestlersAction = Double::for(AddWrestlersToMatchAction::class);
    $addTagTeamsAction = Double::for(AddTagTeamsToMatchAction::class);
    $addTagTeamsAction->expects('handle')->times(2);

    $this->app->instance(AddWrestlersToMatchAction::class, $addWrestlersAction);
    $this->app->instance(AddTagTeamsToMatchAction::class, $addTagTeamsAction);

    AddCompetitorsToMatchAction::run($eventMatch, $competitors);

    $addTagTeamsAction->verify();
    $addWrestlersAction->unused();
});
