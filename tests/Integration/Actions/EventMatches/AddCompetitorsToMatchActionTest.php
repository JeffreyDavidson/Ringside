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

    // Mock the sub-actions in the container
    $this->app->instance(AddWrestlersToMatchAction::class, $this->mock(AddWrestlersToMatchAction::class));
    $this->app->instance(AddTagTeamsToMatchAction::class, $this->mock(AddTagTeamsToMatchAction::class));
});

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

    $addWrestlersAction = $this->app->make(AddWrestlersToMatchAction::class);
    $addTagTeamsAction = $this->app->make(AddTagTeamsToMatchAction::class);

    $addWrestlersAction
        ->shouldReceive('handle')
        ->withAnyArgs()
        ->twice();

    $addTagTeamsAction
        ->shouldNotReceive('handle');

    AddCompetitorsToMatchAction::run($eventMatch, $competitors);
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

    $addWrestlersAction = $this->app->make(AddWrestlersToMatchAction::class);
    $addTagTeamsAction = $this->app->make(AddTagTeamsToMatchAction::class);

    $addTagTeamsAction
        ->shouldReceive('handle')
        ->withAnyArgs()
        ->twice();

    $addWrestlersAction
        ->shouldNotReceive('handle');

    AddCompetitorsToMatchAction::run($eventMatch, $competitors);
});
