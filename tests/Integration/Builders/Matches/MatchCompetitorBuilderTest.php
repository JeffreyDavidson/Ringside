<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;

use function Pest\Laravel\assertModelExists;

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
    // Arrange
    $selectedEvent = Event::factory()->create();
    $secondSelectedEvent = Event::factory()->create();
    $otherEvent = Event::factory()->create();
    $selectedMatch = EventMatch::factory()->forEvent($selectedEvent)->create();
    $secondSelectedMatch = EventMatch::factory()->forEvent($selectedEvent)->create();
    $secondEventMatch = EventMatch::factory()->forEvent($secondSelectedEvent)->create();
    $deletedMatch = EventMatch::factory()->forEvent($selectedEvent)->trashed()->create();
    $otherMatch = EventMatch::factory()->forEvent($otherEvent)->create();
    $selectedRecord = MatchCompetitor::factory()->for($selectedMatch, 'eventMatch')->create();
    $secondCompetitorRecord = MatchCompetitor::factory()->for($selectedMatch, 'eventMatch')->create([
        'match_side_id' => $selectedRecord->match_side_id,
    ]);
    $secondMatchRecord = MatchCompetitor::factory()->for($secondSelectedMatch, 'eventMatch')->create();
    $secondEventRecord = MatchCompetitor::factory()->for($secondEventMatch, 'eventMatch')->create();
    $historicalRecord = MatchCompetitor::factory()->for($deletedMatch, 'eventMatch')->create();
    MatchCompetitor::factory()->for($otherMatch, 'eventMatch')->create();

    // Act
    $query = MatchCompetitor::query();
    $query->forEventIds(collect([$selectedEvent->id, $secondSelectedEvent->id]));
    $query->orderBy('id');
    $records = $query->get();
    $emptyQuery = MatchCompetitor::query();
    $emptyQuery->forEventIds(collect());
    $emptyRecords = $emptyQuery->get();

    // Assert
    expect($records->modelKeys())->toBe([
        $selectedRecord->id,
        $secondCompetitorRecord->id,
        $secondMatchRecord->id,
        $secondEventRecord->id,
    ])->and($emptyRecords)->toBeEmpty();

    assertModelExists($historicalRecord);
});
