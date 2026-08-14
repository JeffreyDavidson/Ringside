<?php

declare(strict_types=1);

use App\Models\Events\Event;
use App\Models\Matches\EventMatch;
use App\Models\TagTeams\TagTeam;
use App\Models\Wrestlers\Wrestler;

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
    $wrestlerMatch->wrestlers()->attach($wrestler, ['side_number' => 1]);

    $tagTeamMatch = EventMatch::factory()->forEvent($event)->create();
    $tagTeamMatch->tagTeams()->attach($tagTeam, ['side_number' => 1]);

    $otherMatch = EventMatch::factory()->forEvent($event)->create();
    $otherMatch->wrestlers()->attach($otherWrestler, ['side_number' => 1]);

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
