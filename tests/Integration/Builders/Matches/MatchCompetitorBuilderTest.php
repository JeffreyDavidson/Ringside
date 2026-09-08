<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

it('filters competitor records by model type and identifiers', function () {
    // Arrange
    $wrestler = Wrestler::factory()->create();
    $otherWrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create(['id' => $wrestler->id]);
    $otherTagTeam = TagTeam::factory()->create();
    $wrestlerRecord = MatchCompetitor::factory()->for($wrestler, 'competitor')->create();
    $tagTeamRecord = MatchCompetitor::factory()->for($tagTeam, 'competitor')->create();
    MatchCompetitor::factory()->for($otherWrestler, 'competitor')->create();
    MatchCompetitor::factory()->for($otherTagTeam, 'competitor')->create();

    // Act
    $wrestlerQuery = MatchCompetitor::query();
    $wrestlerQuery->forWrestlerIds(collect([$wrestler->id]));
    $wrestlerRecords = $wrestlerQuery->get();
    $tagTeamQuery = MatchCompetitor::query();
    $tagTeamQuery->forTagTeamIds(collect([$tagTeam->id]));
    $tagTeamRecords = $tagTeamQuery->get();
    $emptyWrestlerQuery = MatchCompetitor::query();
    $emptyWrestlerQuery->forWrestlerIds(collect());
    $emptyWrestlerRecords = $emptyWrestlerQuery->get();
    $emptyTagTeamQuery = MatchCompetitor::query();
    $emptyTagTeamQuery->forTagTeamIds(collect());
    $emptyTagTeamRecords = $emptyTagTeamQuery->get();

    // Assert
    expect($wrestlerRecords->modelKeys())->toBe([$wrestlerRecord->id])
        ->and($tagTeamRecords->modelKeys())->toBe([$tagTeamRecord->id])
        ->and($emptyWrestlerRecords)->toBeEmpty()
        ->and($emptyTagTeamRecords)->toBeEmpty();
});

it('filters competitor records by their events', function () {
    $selectedEvent = Event::factory()->create();
    $otherEvent = Event::factory()->create();
    $selectedMatch = EventMatch::factory()->forEvent($selectedEvent)->create();
    $otherMatch = EventMatch::factory()->forEvent($otherEvent)->create();
    $selectedRecord = MatchCompetitor::factory()->for($selectedMatch, 'eventMatch')->create();
    MatchCompetitor::factory()->for($otherMatch, 'eventMatch')->create();

    $records = MatchCompetitor::query()
        ->forEventIds(collect([$selectedEvent->id]))
        ->get();

    expect($records)->toHaveCount(1)
        ->and($records->firstOrFail()->match_id)->toBe($selectedRecord->match_id)
        ->and($records->firstOrFail()->competitor_id)->toBe($selectedRecord->competitor_id);
});
