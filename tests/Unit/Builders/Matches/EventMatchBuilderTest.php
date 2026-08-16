<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\Matches\MatchCompetitor;
use App\Models\Matches\MatchSide;
use App\Models\Roster\Referees\Referee;
use App\Models\Roster\TagTeams\TagTeam;
use App\Models\Roster\Wrestlers\Wrestler;
use App\Models\Titles\Title;

function attachBuilderTestCompetitor(EventMatch $match, Wrestler|TagTeam $competitor): void
{
    $side = MatchSide::factory()->for($match, 'match')->create(['position' => 1]);

    MatchCompetitor::factory()->create([
        'match_id' => $match->id,
        'match_side_id' => $side->id,
        'competitor_type' => $competitor->getMorphClass(),
        'competitor_id' => $competitor->id,
    ]);
}

it('retrieves matches for selected events', function () {
    $selectedEvent = Event::factory()->create();
    $otherEvent = Event::factory()->create();
    $selectedMatch = EventMatch::factory()->forEvent($selectedEvent)->create();
    EventMatch::factory()->forEvent($otherEvent)->create();

    $matches = EventMatch::query()
        ->forEventIds(collect([$selectedEvent->id]))
        ->get();

    expect($matches)->toHaveCount(1)
        ->and($matches->firstOrFail()->is($selectedMatch))->toBeTrue();
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

it('retrieves matches for a competitor and eager loads competitors', function () {
    $event = Event::factory()->past()->create();
    $wrestler = Wrestler::factory()->create();
    $otherWrestler = Wrestler::factory()->create();
    $tagTeam = TagTeam::factory()->create();
    $wrestlerMatch = EventMatch::factory()->forEvent($event)->create();
    attachBuilderTestCompetitor($wrestlerMatch, $wrestler);

    $tagTeamMatch = EventMatch::factory()->forEvent($event)->create();
    attachBuilderTestCompetitor($tagTeamMatch, $tagTeam);

    $otherMatch = EventMatch::factory()->forEvent($event)->create();
    attachBuilderTestCompetitor($otherMatch, $otherWrestler);

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
