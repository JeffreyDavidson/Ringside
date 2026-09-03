<?php

declare(strict_types=1);

use App\Enums\MatchType;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Lifecycle\Matches\MatchCompetitorRequirements;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

it('rejects competitor types unsupported by the match format', function () {
    $match = EventMatch::factory()->withMatchType(MatchType::Singles)->create();
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = Wrestler::factory()->bookable()->create();

    expect(fn () => resolve(MatchCompetitorRequirements::class)->ensureSatisfied($match, collect([
        ['tag_teams' => [$tagTeam]],
        ['wrestlers' => [$wrestler]],
    ])))->toThrow(
        InvalidMatchConfigurationException::class,
        'The [Singles] match does not support the selected competitor type.',
    );
});

it('rejects a wrestler represented directly and by a selected tag team', function () {
    $match = EventMatch::factory()->withMatchType(MatchType::TagTeam)->create();
    $tagTeam = TagTeam::factory()->bookable()->create();
    $representedWrestler = $tagTeam->currentWrestlers()->firstOrFail();

    expect(fn () => resolve(MatchCompetitorRequirements::class)->ensureSatisfied($match, collect([
        ['tag_teams' => [$tagTeam]],
        ['wrestlers' => [$representedWrestler]],
    ])))->toThrow(
        InvalidMatchConfigurationException::class,
        'A wrestler cannot compete directly and through a selected tag team in the same match.',
    );
});

it('accepts mixed competitors that satisfy a multi-person tag composition', function () {
    $match = EventMatch::factory()->withMatchType(MatchType::SixManTagTeam)->create();
    $firstTagTeam = TagTeam::factory()->bookable()->create();
    $secondTagTeam = TagTeam::factory()->bookable()->create();
    $firstWrestler = Wrestler::factory()->bookable()->create();
    $secondWrestler = Wrestler::factory()->bookable()->create();

    resolve(MatchCompetitorRequirements::class)->ensureSatisfied($match, collect([
        ['tag_teams' => [$firstTagTeam], 'wrestlers' => [$firstWrestler]],
        ['tag_teams' => [$secondTagTeam], 'wrestlers' => [$secondWrestler]],
    ]));
})->throwsNoExceptions();

it('rejects an incorrect multi-person tag composition', function () {
    $match = EventMatch::factory()->withMatchType(MatchType::SixManTagTeam)->create();
    $firstTagTeam = TagTeam::factory()->bookable()->create();
    $secondTagTeam = TagTeam::factory()->bookable()->create();

    expect(fn () => resolve(MatchCompetitorRequirements::class)->ensureSatisfied($match, collect([
        ['tag_teams' => [$firstTagTeam]],
        ['tag_teams' => [$secondTagTeam]],
    ])))->toThrow(
        InvalidMatchConfigurationException::class,
        'The [6 Man Tag Team] match requires a 3-on-3 roster-member composition.',
    );
});

it('accepts a handicap composition in either side order', function () {
    $match = EventMatch::factory()->withMatchType(MatchType::TwoOnOneHandicap)->create();
    $tagTeam = TagTeam::factory()->bookable()->create();
    $wrestler = Wrestler::factory()->bookable()->create();

    resolve(MatchCompetitorRequirements::class)->ensureSatisfied($match, collect([
        ['tag_teams' => [$tagTeam]],
        ['wrestlers' => [$wrestler]],
    ]));
})->throwsNoExceptions();

it('rejects multiple competitor entries on an independently competing side', function () {
    $match = EventMatch::factory()->withMatchType(MatchType::TripleThreat)->create();
    $firstWrestler = Wrestler::factory()->bookable()->create();
    $secondWrestler = Wrestler::factory()->bookable()->create();
    $thirdWrestler = Wrestler::factory()->bookable()->create();
    $fourthWrestler = Wrestler::factory()->bookable()->create();

    expect(fn () => resolve(MatchCompetitorRequirements::class)->ensureSatisfied($match, collect([
        ['wrestlers' => [$firstWrestler, $secondWrestler]],
        ['wrestlers' => [$thirdWrestler]],
        ['wrestlers' => [$fourthWrestler]],
    ])))->toThrow(
        InvalidMatchConfigurationException::class,
        'The [Triple Threat] match requires a 1-on-1-on-1 competitor-entry composition.',
    );
});

it('requires an individual side for every elimination-match entrant', function () {
    $match = EventMatch::factory()->withMatchType(MatchType::BattleRoyal)->create();
    $firstWrestler = Wrestler::factory()->bookable()->create();
    $secondWrestler = Wrestler::factory()->bookable()->create();
    $thirdWrestler = Wrestler::factory()->bookable()->create();

    expect(fn () => resolve(MatchCompetitorRequirements::class)->ensureSatisfied($match, collect([
        ['wrestlers' => [$firstWrestler, $secondWrestler]],
        ['wrestlers' => [$thirdWrestler]],
    ])))->toThrow(
        InvalidMatchConfigurationException::class,
        'Each [Battle Royal] entrant must compete on an individual side.',
    );
});
