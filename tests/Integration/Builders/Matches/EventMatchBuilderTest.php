<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;
use Illuminate\Support\Facades\Date;

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
    // Arrange
    $this->freezeSecond();
    $pastEvent = Event::factory()->create(['date' => Date::now()->subSecond()]);
    $currentEvent = Event::factory()->create(['date' => Date::now()]);
    $scheduledEvent = Event::factory()->create(['date' => Date::now()->addSecond()]);
    $unscheduledEvent = Event::factory()->unscheduled()->create();
    $deletedEvent = Event::factory()->past()->trashed()->create();
    $pastMatch = EventMatch::factory()->forEvent($pastEvent)->create();
    EventMatch::factory()->forEvent($pastEvent)->trashed()->create();
    EventMatch::factory()->forEvent($currentEvent)->create();
    EventMatch::factory()->forEvent($scheduledEvent)->create();
    EventMatch::factory()->forEvent($unscheduledEvent)->create();
    EventMatch::factory()->forEvent($deletedEvent)->create();

    // Act
    $query = EventMatch::query();
    $query->forPastEvents();
    $matches = $query->get();

    // Assert
    expect($matches->modelKeys())->toBe([$pastMatch->id])
        ->and($matches->firstOrFail()->relationLoaded('event'))->toBeTrue()
        ->and($matches->firstOrFail()->event->is($pastEvent))->toBeTrue();
});

it('retrieves match history with its display relationships eager loaded and ordered', function () {
    // Arrange
    $this->freezeSecond();
    $pastEvent = Event::factory()->past()->create();
    $olderEvent = Event::factory()->create(['date' => Date::now()->subDays(2)]);
    $scheduledEvent = Event::factory()->scheduled()->create();
    $wrestler = Wrestler::factory()->create();
    $olderMatch = EventMatch::factory()->forEvent($olderEvent)->create();
    $match = EventMatch::factory()->forEvent($pastEvent)->create();
    EventMatch::factory()->forEvent($scheduledEvent)->create();
    MatchCompetitor::factory()->for($match, 'eventMatch')->for($wrestler, 'competitor')->create();

    // Act
    $query = EventMatch::query();
    $query->forHistory();
    $history = $query->get();

    // Assert
    expect($history->modelKeys())->toBe([$match->id, $olderMatch->id])
        ->and($history->firstOrFail()->relationLoaded('event'))->toBeTrue()
        ->and($history->firstOrFail()->relationLoaded('referees'))->toBeTrue()
        ->and($history->firstOrFail()->relationLoaded('titles'))->toBeTrue()
        ->and($history->firstOrFail()->relationLoaded('competitors'))->toBeTrue()
        ->and($history->firstOrFail()->relationLoaded('winningSide'))->toBeTrue();

    $competitor = $history->firstOrFail()->competitors->firstOrFail();

    expect($competitor->relationLoaded('competitor'))->toBeTrue()
        ->and($competitor->relationLoaded('side'))->toBeTrue()
        ->and($competitor->competitor->is($wrestler))->toBeTrue();
});

it('retrieves matches for a competitor and eager loads competitors', function () {
    // Arrange
    $event = Event::factory()->past()->create();
    $wrestler = Wrestler::factory()->create();
    $otherWrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create(['id' => $wrestler->id]);
    $otherTagTeam = TagTeam::factory()->create();
    $wrestlerMatch = EventMatch::factory()->forEvent($event)->create();
    MatchCompetitor::factory()->for($wrestlerMatch, 'eventMatch')->for($wrestler, 'competitor')->create();

    $tagTeamMatch = EventMatch::factory()->forEvent($event)->create();
    MatchCompetitor::factory()->for($tagTeamMatch, 'eventMatch')->for($tagTeam, 'competitor')->create();

    $otherMatch = EventMatch::factory()->forEvent($event)->create();
    MatchCompetitor::factory()->for($otherMatch, 'eventMatch')->for($otherWrestler, 'competitor')->create();
    $otherTagTeamMatch = EventMatch::factory()->forEvent($event)->create();
    MatchCompetitor::factory()->for($otherTagTeamMatch, 'eventMatch')->for($otherTagTeam, 'competitor')->create();

    // Act
    $wrestlerQuery = EventMatch::query();
    $wrestlerQuery->forCompetitor($wrestler);
    $wrestlerMatches = $wrestlerQuery->get();
    $tagTeamQuery = EventMatch::query();
    $tagTeamQuery->forCompetitor($tagTeam);
    $tagTeamMatches = $tagTeamQuery->get();

    // Assert
    expect($wrestlerMatches->modelKeys())->toBe([$wrestlerMatch->id])
        ->and($wrestlerMatches->firstOrFail()->relationLoaded('competitors'))->toBeTrue()
        ->and($tagTeamMatches->modelKeys())->toBe([$tagTeamMatch->id])
        ->and($tagTeamMatches->firstOrFail()->relationLoaded('competitors'))->toBeTrue();
});

it('retrieves matches by wrestler and tag team ids', function () {
    // Arrange
    $event = Event::factory()->past()->create();
    $wrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create(['id' => $wrestler->id]);
    $unassignedWrestler = Wrestler::factory()->create();
    $unassignedTagTeam = TagTeam::factory()->create();
    $wrestlerMatch = EventMatch::factory()->forEvent($event)->create();
    MatchCompetitor::factory()->for($wrestlerMatch, 'eventMatch')->for($wrestler, 'competitor')->create();
    $tagTeamMatch = EventMatch::factory()->forEvent($event)->create();
    MatchCompetitor::factory()->for($tagTeamMatch, 'eventMatch')->for($tagTeam, 'competitor')->create();

    // Act
    $wrestlerQuery = EventMatch::query();
    $wrestlerQuery->forWrestlerId($wrestler->id);
    $wrestlerMatches = $wrestlerQuery->get();
    $tagTeamQuery = EventMatch::query();
    $tagTeamQuery->forTagTeamId($tagTeam->id);
    $tagTeamMatches = $tagTeamQuery->get();
    $emptyWrestlerQuery = EventMatch::query();
    $emptyWrestlerQuery->forWrestlerId($unassignedWrestler->id);
    $emptyWrestlerMatches = $emptyWrestlerQuery->get();
    $emptyTagTeamQuery = EventMatch::query();
    $emptyTagTeamQuery->forTagTeamId($unassignedTagTeam->id);
    $emptyTagTeamMatches = $emptyTagTeamQuery->get();

    // Assert
    expect($wrestlerMatches->modelKeys())->toBe([$wrestlerMatch->id])
        ->and($wrestlerMatches->firstOrFail()->relationLoaded('competitors'))->toBeTrue()
        ->and($tagTeamMatches->modelKeys())->toBe([$tagTeamMatch->id])
        ->and($tagTeamMatches->firstOrFail()->relationLoaded('competitors'))->toBeTrue()
        ->and($emptyWrestlerMatches)->toBeEmpty()
        ->and($emptyTagTeamMatches)->toBeEmpty();
});

it('retrieves matches officiated by a referee and eager loads every assigned referee', function () {
    // Arrange
    $referee = Referee::factory()->create();
    $otherReferee = Referee::factory()->create();
    $officiatedMatch = EventMatch::factory()->create();
    $otherMatch = EventMatch::factory()->create();
    $officiatedMatch->referees()->attach([$referee->id, $otherReferee->id]);
    $otherMatch->referees()->attach($otherReferee);

    // Act
    $query = EventMatch::query();
    $query->forReferee($referee);
    $matches = $query->get();

    // Assert
    expect($matches->modelKeys())->toBe([$officiatedMatch->id])
        ->and($matches->firstOrFail()->relationLoaded('referees'))->toBeTrue()
        ->and($matches->firstOrFail()->referees->modelKeys())->toEqualCanonicalizing([$referee->id, $otherReferee->id]);
});

it('retrieves matches officiated by a referee id', function () {
    // Arrange
    $referee = Referee::factory()->create();
    $otherReferee = Referee::factory()->create();
    $unassignedReferee = Referee::factory()->create();
    $match = EventMatch::factory()->create();
    $match->referees()->attach([$referee->id, $otherReferee->id]);
    EventMatch::factory()->create()->referees()->attach($otherReferee);
    EventMatch::factory()->create();

    // Act
    $query = EventMatch::query();
    $query->forRefereeId($referee->id);
    $matches = $query->get();
    $emptyQuery = EventMatch::query();
    $emptyQuery->forRefereeId($unassignedReferee->id);
    $emptyMatches = $emptyQuery->get();

    // Assert
    expect($matches->modelKeys())->toBe([$match->id])
        ->and($matches->firstOrFail()->relationLoaded('referees'))->toBeTrue()
        ->and($matches->firstOrFail()->referees->modelKeys())->toEqualCanonicalizing([$referee->id, $otherReferee->id])
        ->and($emptyMatches)->toBeEmpty();
});

it('retrieves matches assigned to any selected referee', function () {
    // Arrange
    $selectedReferee = Referee::factory()->create();
    $secondReferee = Referee::factory()->create();
    $otherReferee = Referee::factory()->create();
    $selectedMatch = EventMatch::factory()->create();
    $selectedMatch->referees()->attach($selectedReferee);
    $secondMatch = EventMatch::factory()->create();
    $secondMatch->referees()->attach($secondReferee);
    $sharedMatch = EventMatch::factory()->create();
    $sharedMatch->referees()->attach([$selectedReferee->id, $secondReferee->id]);
    EventMatch::factory()->create()->referees()->attach($otherReferee);
    EventMatch::factory()->create();

    // Act
    $query = EventMatch::query();
    $query->withAnyRefereeIds(collect([$selectedReferee->id, $secondReferee->id]));
    $query->orderBy('id');
    $matches = $query->get();
    $emptyQuery = EventMatch::query();
    $emptyQuery->withAnyRefereeIds(collect());
    $emptyMatches = $emptyQuery->get();

    // Assert
    expect($matches->modelKeys())->toBe([$selectedMatch->id, $secondMatch->id, $sharedMatch->id])
        ->and($emptyMatches)->toBeEmpty();
});

it('retrieves matches assigned to any selected title', function () {
    // Arrange
    $selectedTitle = Title::factory()->create();
    $secondTitle = Title::factory()->create();
    $otherTitle = Title::factory()->create();
    $selectedMatch = EventMatch::factory()->create();
    $selectedMatch->titles()->attach($selectedTitle);
    $secondMatch = EventMatch::factory()->create();
    $secondMatch->titles()->attach($secondTitle);
    $sharedMatch = EventMatch::factory()->create();
    $sharedMatch->titles()->attach([$selectedTitle->id, $secondTitle->id]);
    EventMatch::factory()->create()->titles()->attach($otherTitle);
    EventMatch::factory()->create();

    // Act
    $query = EventMatch::query();
    $query->withAnyTitleIds(collect([$selectedTitle->id, $secondTitle->id]));
    $query->orderBy('id');
    $matches = $query->get();
    $emptyQuery = EventMatch::query();
    $emptyQuery->withAnyTitleIds(collect());
    $emptyMatches = $emptyQuery->get();

    // Assert
    expect($matches->modelKeys())->toBe([$selectedMatch->id, $secondMatch->id, $sharedMatch->id])
        ->and($emptyMatches)->toBeEmpty();
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
