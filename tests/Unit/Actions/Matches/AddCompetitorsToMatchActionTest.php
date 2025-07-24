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
    [$wrestlerA, $wrestlerB] = Wrestler::factory()->bookable()->count(2)->create();
    $competitors = collect([
        0 => [
            'wrestlers' => collect([$wrestlerA]),
        ],
        1 => [
            'wrestlers' => collect([$wrestlerB]),
        ],
    ]);

    $addWrestlersToMatchAction = $this->mock(AddWrestlersToMatchAction::class);
    $addTagTeamsToMatchAction = $this->mock(AddTagTeamsToMatchAction::class);

    app()->instance(AddWrestlersToMatchAction::class, $addWrestlersToMatchAction);
    app()->instance(AddTagTeamsToMatchAction::class, $addTagTeamsToMatchAction);

    $addWrestlersToMatchAction
        ->shouldReceive('handle')
        ->with($eventMatch, Mockery::type('Illuminate\Support\Collection'), 0)
        ->once();

    $addWrestlersToMatchAction
        ->shouldReceive('handle')
        ->with($eventMatch, Mockery::type('Illuminate\Support\Collection'), 1)
        ->once();

    $addTagTeamsToMatchAction
        ->shouldNotReceive('handle');

    AddCompetitorsToMatchAction::run($eventMatch, $competitors);
});

test('it adds tag team competitors to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    [$tagTeamA, $tagTeamB] = TagTeam::factory()->bookable()->count(2)->create();
    $competitors = collect([
        0 => [
            'tag_teams' => collect([$tagTeamA]),
        ],
        1 => [
            'tag_teams' => collect([$tagTeamB]),
        ],
    ]);

    $addWrestlersToMatchAction = $this->mock(AddWrestlersToMatchAction::class);
    $addTagTeamsToMatchAction = $this->mock(AddTagTeamsToMatchAction::class);

    app()->instance(AddWrestlersToMatchAction::class, $addWrestlersToMatchAction);
    app()->instance(AddTagTeamsToMatchAction::class, $addTagTeamsToMatchAction);

    $addTagTeamsToMatchAction
        ->shouldReceive('handle')
        ->with($eventMatch, Mockery::type('Illuminate\Support\Collection'), 0)
        ->once();

    $addTagTeamsToMatchAction
        ->shouldReceive('handle')
        ->with($eventMatch, Mockery::type('Illuminate\Support\Collection'), 1)
        ->once();

    $addWrestlersToMatchAction
        ->shouldNotReceive('handle');

    AddCompetitorsToMatchAction::run($eventMatch, $competitors);
});
