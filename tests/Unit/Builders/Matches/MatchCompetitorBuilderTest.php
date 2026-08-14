<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

it('filters competitor records by model type and identifiers', function () {
    $wrestler = Wrestler::factory()->create();
    $otherWrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $wrestlerRecord = MatchCompetitor::factory()->create([
        'competitor_type' => Wrestler::class,
        'competitor_id' => $wrestler->id,
    ]);
    MatchCompetitor::factory()->create([
        'competitor_type' => Wrestler::class,
        'competitor_id' => $otherWrestler->id,
    ]);
    MatchCompetitor::factory()->create([
        'competitor_type' => TagTeam::class,
        'competitor_id' => $tagTeam->id,
    ]);

    $records = MatchCompetitor::query()
        ->forCompetitorIds(Wrestler::class, collect([$wrestler->id]))
        ->get();

    expect($records)->toHaveCount(1)
        ->and($records->firstOrFail()->match_id)->toBe($wrestlerRecord->match_id)
        ->and($records->firstOrFail()->competitor_type)->toBe(Wrestler::class)
        ->and($records->firstOrFail()->competitor_id)->toBe($wrestler->id);
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
