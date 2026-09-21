<?php

declare(strict_types=1);

use App\Actions\Matches\AddCompetitorsToMatchAction;
use App\Actions\Matches\AddTagTeamsToMatchAction;
use App\Actions\Matches\AddWrestlersToMatchAction;
use App\Enums\MatchType;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use JMac\Testing\Double;

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

    $addWrestlersAction = Double::for(AddWrestlersToMatchAction::class);
    $addTagTeamsAction = Double::for(AddTagTeamsToMatchAction::class);
    $addWrestlersAction->expects('handleWithinTransaction')->times(2);

    $this->app->instance(AddWrestlersToMatchAction::class, $addWrestlersAction);
    $this->app->instance(AddTagTeamsToMatchAction::class, $addTagTeamsAction);

    resolve(AddCompetitorsToMatchAction::class)->handle($eventMatch, $competitors);

    $addWrestlersAction->verify();
    $addTagTeamsAction->unused();
});

test('it adds tag team competitors to a match', function () {
    $eventMatch = EventMatch::factory()->withMatchType(MatchType::TagTeam)->create();
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

    $addWrestlersAction = Double::for(AddWrestlersToMatchAction::class);
    $addTagTeamsAction = Double::for(AddTagTeamsToMatchAction::class);
    $addTagTeamsAction->expects('handleWithinTransaction')->times(2);

    $this->app->instance(AddWrestlersToMatchAction::class, $addWrestlersAction);
    $this->app->instance(AddTagTeamsToMatchAction::class, $addTagTeamsAction);

    resolve(AddCompetitorsToMatchAction::class)->handle($eventMatch, $competitors);

    $addTagTeamsAction->verify();
    $addWrestlersAction->unused();
});

test('it rejects competitor assignments without the required populated sides', function () {
    $eventMatch = EventMatch::factory()->create();
    $wrestler = Wrestler::factory()->bookable()->create();
    $competitors = collect([
        1 => [
            'wrestlers' => [$wrestler],
        ],
    ]);

    expect(fn () => resolve(AddCompetitorsToMatchAction::class)->handle($eventMatch, $competitors))
        ->toThrow(InvalidMatchConfigurationException::class, 'This match requires exactly 2 competitor sides.');
});

test('it rejects duplicate competitors across match sides', function () {
    $eventMatch = EventMatch::factory()->create();
    $wrestler = Wrestler::factory()->bookable()->create();
    $competitors = collect([
        ['wrestlers' => [$wrestler]],
        ['wrestlers' => [$wrestler]],
    ]);

    expect(fn () => resolve(AddCompetitorsToMatchAction::class)->handle($eventMatch, $competitors))
        ->toThrow(InvalidMatchConfigurationException::class, 'The same competitor cannot compete multiple times in a match.');
});

test('it enforces individual entrant limits outside form validation', function () {
    $eventMatch = EventMatch::factory()->withMatchType(MatchType::RoyalRumble)->create();
    $wrestlers = Wrestler::factory()->bookable()->count(9)->create();
    $competitors = $wrestlers->map(fn (Wrestler $wrestler): array => ['wrestlers' => [$wrestler]]);

    expect(fn () => resolve(AddCompetitorsToMatchAction::class)->handle($eventMatch, $competitors))
        ->toThrow(InvalidMatchConfigurationException::class, 'This match requires between 10 and 30 competitors.');
});
