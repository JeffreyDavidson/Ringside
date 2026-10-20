<?php

declare(strict_types=1);

use App\Enums\Matches\MatchType;
use App\Exceptions\Matches\InvalidMatchConfigurationException;
use App\Lifecycle\Matches\MatchCompetitorRequirements;
use App\Models\Matches\EventMatch;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\ValueObjects\MatchSides;

it('reports the side count before the competitor count', function () {
    // Arrange
    $match = EventMatch::factory()
        ->withMatchType(MatchType::Singles)
        ->create();
    $sides = new MatchSides(collect([
        ['wrestlers' => [Wrestler::factory()->bookable()->create()]],
        ['wrestlers' => [Wrestler::factory()->bookable()->create()]],
        ['wrestlers' => [Wrestler::factory()->bookable()->create()]],
    ]));

    // Act
    $attempt = fn () => resolve(MatchCompetitorRequirements::class)->ensureSatisfied($match, $sides);

    // Assert
    expect($attempt)->toThrow(
        InvalidMatchConfigurationException::class,
        InvalidMatchConfigurationException::incorrectSideCount(2)->getMessage(),
    );
});

it('reports the competitor count before the competitor type', function () {
    // Arrange
    $match = EventMatch::factory()
        ->withMatchType(MatchType::RoyalRumble)
        ->create();
    $sides = new MatchSides(collect([
        ['tag_teams' => [TagTeam::factory()->bookable()->create()]],
        ['wrestlers' => [Wrestler::factory()->bookable()->create()]],
    ]));

    // Act
    $attempt = fn () => resolve(MatchCompetitorRequirements::class)->ensureSatisfied($match, $sides);

    // Assert
    expect($attempt)->toThrow(
        InvalidMatchConfigurationException::class,
        InvalidMatchConfigurationException::invalidCompetitorCount(10, 30)->getMessage(),
    );
});

it('reports the competitor type before duplicates', function () {
    // Arrange
    $match = EventMatch::factory()
        ->withMatchType(MatchType::Singles)
        ->create();
    $tagTeam = TagTeam::factory()
        ->bookable()
        ->create();
    $sides = new MatchSides(collect([
        ['tag_teams' => [$tagTeam]],
        ['tag_teams' => [$tagTeam]],
    ]));

    // Act
    $attempt = fn () => resolve(MatchCompetitorRequirements::class)->ensureSatisfied($match, $sides);

    // Assert
    expect($attempt)->toThrow(
        InvalidMatchConfigurationException::class,
        InvalidMatchConfigurationException::unsupportedCompetitorType(MatchType::Singles)->getMessage(),
    );
});

it('reports duplicates before the entries per side', function () {
    // Arrange
    $match = EventMatch::factory()
        ->withMatchType(MatchType::TripleThreat)
        ->create();
    $repeated = Wrestler::factory()
        ->bookable()
        ->create();
    $sides = new MatchSides(collect([
        ['wrestlers' => [$repeated]],
        ['wrestlers' => [$repeated]],
        ['wrestlers' => [Wrestler::factory()->bookable()->create(), Wrestler::factory()->bookable()->create()]],
    ]));

    // Act
    $attempt = fn () => resolve(MatchCompetitorRequirements::class)->ensureSatisfied($match, $sides);

    // Assert
    expect($attempt)->toThrow(
        InvalidMatchConfigurationException::class,
        InvalidMatchConfigurationException::duplicateCompetitors()->getMessage(),
    );
});

it('reports a wrestler represented by a selected tag team before the roster composition', function () {
    // Arrange
    $match = EventMatch::factory()
        ->withMatchType(MatchType::TagTeam)
        ->create();
    $tagTeam = TagTeam::factory()
        ->bookable()
        ->create();
    $sides = new MatchSides(collect([
        ['tag_teams' => [$tagTeam]],
        ['wrestlers' => [$tagTeam->currentWrestlers()->firstOrFail()]],
    ]));

    // Act
    $attempt = fn () => resolve(MatchCompetitorRequirements::class)->ensureSatisfied($match, $sides);

    // Assert
    expect($attempt)->toThrow(
        InvalidMatchConfigurationException::class,
        InvalidMatchConfigurationException::duplicateCompetitorRepresentation()->getMessage(),
    );
});

it('ignores sides without competitors when counting sides and entries', function () {
    // Arrange
    $match = EventMatch::factory()
        ->withMatchType(MatchType::Singles)
        ->create();
    $sides = new MatchSides(collect([
        ['wrestlers' => [Wrestler::factory()->bookable()->create()]],
        ['wrestlers' => []],
        ['wrestlers' => [Wrestler::factory()->bookable()->create()]],
    ]));

    // Act
    $attempt = fn () => resolve(MatchCompetitorRequirements::class)->ensureSatisfied($match, $sides);

    // Assert
    expect($attempt)->not->toThrow(InvalidMatchConfigurationException::class);
});
