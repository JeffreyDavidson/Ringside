<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

it('retrieves matches for selected events', function () {
    // Arrange
    $selectedEvent = Event::factory()->create();
    $secondSelectedEvent = Event::factory()->create();
    $otherEvent = Event::factory()->create();
    $selectedMatch = EventMatch::factory()->forEvent($selectedEvent)->create();
    $secondMatch = EventMatch::factory()->forEvent($selectedEvent)->create();
    $secondEventMatch = EventMatch::factory()->forEvent($secondSelectedEvent)->create();
    EventMatch::factory()->forEvent($selectedEvent)->trashed()->create();
    EventMatch::factory()->forEvent($otherEvent)->create();

    // Act
    $query = EventMatch::query();
    $query->forEventIds(collect([$selectedEvent->id, $secondSelectedEvent->id]));
    $query->orderBy('id');
    $matches = $query->get();
    $emptyQuery = EventMatch::query();
    $emptyQuery->forEventIds(collect());
    $emptyMatches = $emptyQuery->get();

    // Assert
    expect($matches->modelKeys())->toBe([$selectedMatch->id, $secondMatch->id, $secondEventMatch->id])
        ->and($emptyMatches)->toBeEmpty();
});

it('retrieves matches for one event by id', function () {
    // Arrange
    $event = Event::factory()->create();
    $emptyEvent = Event::factory()->create();
    $match = EventMatch::factory()->forEvent($event)->create();
    $secondMatch = EventMatch::factory()->forEvent($event)->create();
    EventMatch::factory()->forEvent($event)->trashed()->create();
    EventMatch::factory()->create();

    // Act
    $query = EventMatch::query();
    $query->forEventId($event->id);
    $query->orderBy('id');
    $matches = $query->get();
    $emptyQuery = EventMatch::query();
    $emptyQuery->forEventId($emptyEvent->id);
    $emptyMatches = $emptyQuery->get();

    // Assert
    expect($matches->modelKeys())->toBe([$match->id, $secondMatch->id])
        ->and($emptyMatches)->toBeEmpty();
});

it('retrieves matches for past events and eager loads their events', function () {
    $pastEvent = Event::factory()->past()->create();
    $scheduledEvent = Event::factory()->scheduled()->create();
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $pastMatch = EventMatch::factory()->forEvent($pastEvent)->create();
    EventMatch::factory()->forEvent($scheduledEvent)->create();
    EventMatch::factory()->forEvent($unscheduledEvent)->create();

    $matches = EventMatch::query()->forPastEvents()->get();

    expect($matches)->toHaveCount(1)
        ->and($matches->firstOrFail()->is($pastMatch))->toBeTrue()
        ->and($matches->firstOrFail()->relationLoaded('event'))->toBeTrue();
});

it('retrieves match history with its display relationships eager loaded and ordered', function () {
    $pastEvent = Event::factory()->past()->create();
    $wrestler = Wrestler::factory()->create();
    $match = EventMatch::factory()->forEvent($pastEvent)->create();
    MatchCompetitor::factory()->for($match, 'eventMatch')->for($wrestler, 'competitor')->create();

    $history = EventMatch::query()->forHistory()->get();

    expect($history)->toHaveCount(1)
        ->and($history->firstOrFail()->is($match))->toBeTrue()
        ->and($history->firstOrFail()->relationLoaded('event'))->toBeTrue()
        ->and($history->firstOrFail()->relationLoaded('referees'))->toBeTrue()
        ->and($history->firstOrFail()->relationLoaded('titles'))->toBeTrue()
        ->and($history->firstOrFail()->relationLoaded('competitors'))->toBeTrue()
        ->and($history->firstOrFail()->relationLoaded('winningSide'))->toBeTrue();
});

it('retrieves matches for a competitor and eager loads competitors', function () {
    $event = Event::factory()->past()->create();
    $wrestler = Wrestler::factory()->create();
    $otherWrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $wrestlerMatch = EventMatch::factory()->forEvent($event)->create();
    MatchCompetitor::factory()->for($wrestlerMatch, 'eventMatch')->for($wrestler, 'competitor')->create();

    $tagTeamMatch = EventMatch::factory()->forEvent($event)->create();
    MatchCompetitor::factory()->for($tagTeamMatch, 'eventMatch')->for($tagTeam, 'competitor')->create();

    $otherMatch = EventMatch::factory()->forEvent($event)->create();
    MatchCompetitor::factory()->for($otherMatch, 'eventMatch')->for($otherWrestler, 'competitor')->create();

    $wrestlerMatches = EventMatch::query()->forCompetitor($wrestler)->get();
    $tagTeamMatches = EventMatch::query()->forCompetitor($tagTeam)->get();

    expect($wrestlerMatches)
        ->toHaveCount(1)
        ->and($wrestlerMatches->contains($wrestlerMatch))->toBeTrue()
        ->and($wrestlerMatches->contains($tagTeamMatch))->toBeFalse()
        ->and($wrestlerMatches->contains($otherMatch))->toBeFalse()
        ->and($wrestlerMatches->firstOrFail()->relationLoaded('competitors'))->toBeTrue()
        ->and($tagTeamMatches)
        ->toHaveCount(1)
        ->and($tagTeamMatches->contains($tagTeamMatch))->toBeTrue();
});

it('retrieves matches by wrestler and tag team ids', function () {
    $event = Event::factory()->past()->create();
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $wrestlerMatch = EventMatch::factory()->forEvent($event)->create();
    MatchCompetitor::factory()->for($wrestlerMatch, 'eventMatch')->for($wrestler, 'competitor')->create();
    $tagTeamMatch = EventMatch::factory()->forEvent($event)->create();
    MatchCompetitor::factory()->for($tagTeamMatch, 'eventMatch')->for($tagTeam, 'competitor')->create();

    expect(EventMatch::query()->forWrestlerId($wrestler->id)->pluck('id')->all())
        ->toBe([$wrestlerMatch->id])
        ->and(EventMatch::query()->forTagTeamId($tagTeam->id)->pluck('id')->all())
        ->toBe([$tagTeamMatch->id]);
});

it('retrieves matches officiated by a referee and eager loads every assigned referee', function () {
    $referee = Referee::factory()->create();
    $otherReferee = Referee::factory()->create();
    $officiatedMatch = EventMatch::factory()->create();
    $otherMatch = EventMatch::factory()->create();
    $officiatedMatch->referees()->attach([$referee->id, $otherReferee->id]);
    $otherMatch->referees()->attach($otherReferee);

    $matches = EventMatch::query()->forReferee($referee)->get();

    expect($matches)->toHaveCount(1)
        ->and($matches->firstOrFail()->is($officiatedMatch))->toBeTrue()
        ->and($matches->firstOrFail()->relationLoaded('referees'))->toBeTrue()
        ->and($matches->firstOrFail()->referees)->toHaveCount(2);
});

it('retrieves matches officiated by a referee id', function () {
    $referee = Referee::factory()->create();
    $match = EventMatch::factory()->create();
    $match->referees()->attach($referee);
    EventMatch::factory()->create();

    expect(EventMatch::query()->forRefereeId($referee->id)->pluck('id')->all())->toBe([$match->id]);
});

it('retrieves matches assigned to any selected referee', function () {
    $selectedReferee = Referee::factory()->create();
    $otherReferee = Referee::factory()->create();
    $selectedMatch = EventMatch::factory()->create();
    $selectedMatch->referees()->attach($selectedReferee);
    EventMatch::factory()->create()->referees()->attach($otherReferee);

    $matches = EventMatch::query()
        ->withAnyRefereeIds(collect([$selectedReferee->id]))
        ->get();

    expect($matches)->toHaveCount(1)
        ->and($matches->firstOrFail()->is($selectedMatch))->toBeTrue();
});

it('retrieves matches assigned to any selected title', function () {
    $selectedTitle = Title::factory()->create();
    $otherTitle = Title::factory()->create();
    $selectedMatch = EventMatch::factory()->create();
    $selectedMatch->titles()->attach($selectedTitle);
    EventMatch::factory()->create()->titles()->attach($otherTitle);

    $matches = EventMatch::query()
        ->withAnyTitleIds(collect([$selectedTitle->id]))
        ->get();

    expect($matches)->toHaveCount(1)
        ->and($matches->firstOrFail()->is($selectedMatch))->toBeTrue();
});

it('orders matches by event date, card, and match number', function () {
    $oldestEvent = Event::factory()->past()->create(['date' => now()->subDays(3)]);
    $latestEvent = Event::factory()->past()->create(['date' => now()->subDay()]);
    $otherLatestEvent = Event::factory()->past()->create(['date' => $latestEvent->date]);
    $middleEvent = Event::factory()->past()->create(['date' => now()->subDays(2)]);
    $oldestMatch = EventMatch::factory()->forEvent($oldestEvent)->create();
    $latestSecondMatch = EventMatch::factory()->forEvent($latestEvent)->create(['match_number' => 2]);
    $latestFirstMatch = EventMatch::factory()->forEvent($latestEvent)->create(['match_number' => 1]);
    $otherLatestMatch = EventMatch::factory()->forEvent($otherLatestEvent)->create(['match_number' => 1]);
    $middleMatch = EventMatch::factory()->forEvent($middleEvent)->create();

    $matches = EventMatch::query()->latestEventFirst()->get();

    expect($matches->modelKeys())->toBe([
        $otherLatestMatch->id,
        $latestFirstMatch->id,
        $latestSecondMatch->id,
        $middleMatch->id,
        $oldestMatch->id,
    ]);
});
