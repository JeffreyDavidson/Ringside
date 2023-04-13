<?php

use App\Data\EventMatchData;
use App\Models\Event;
use App\Models\EventMatch;
use App\Models\MatchType;
use App\Models\Referee;
use App\Models\TagTeam;
use App\Models\Title;
use App\Models\Wrestler;
use App\Repositories\EventMatchRepository;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
});

test('creates an event match', function () {
    $event = Event::factory()->create();
    $matchType = MatchType::inRandomOrder()->first();
    $referees = Referee::factory()->count(1)->create();
    $data = new EventMatchData($matchType, $referees, null, collect(), null);

    $eventMatch = app(EventMatchRepository::class)->createForEvent($event, $data);

    expect($eventMatch)
        ->match_type_id->toEqual($matchType->id)
        ->preview->toBeNull();
});

test('creates an event match with a preview', function () {
    $event = Event::factory()->create();
    $matchType = MatchType::inRandomOrder()->first();
    $referees = Referee::factory()->count(1)->create();
    $data = new EventMatchData($matchType, $referees, null, collect(), 'This is an general preview.');

    $eventMatch = app(EventMatchRepository::class)->createForEvent($event, $data);

    expect($eventMatch)
        ->match_type_id->toEqual($matchType->id)
        ->preview->toEqual('This is an general preview.');
});

test('it adds a referee to an event match', function () {
    $eventMatch = EventMatch::factory()->create();
    $referee = Referee::factory()->create();

    app(EventMatchRepository::class)->addRefereeToMatch($eventMatch, $referee);

    expect($eventMatch->fresh())
        ->referees->toHaveCount(1)
        ->referees->collectionHas($referee);
});

test('it adds a title to an event match', function () {
    $eventMatch = EventMatch::factory()->create();
    $title = Title::factory()->create();

    app(EventMatchRepository::class)->addTitleToMatch($eventMatch, $title);

    expect($eventMatch->fresh())
        ->titles->toHaveCount(1)
        ->titles->collectionHas($title);
});

test('it adds a wrestler to an event match', function () {
    $eventMatch = EventMatch::factory()->create();
    $wrestler = Wrestler::factory()->create();

    app(EventMatchRepository::class)->addWrestlerToMatch($eventMatch, $wrestler, 0);

    expect($eventMatch->fresh())
        ->wrestlers->toHaveCount(1)
        ->wrestlers->each(function ($wrestler) {
            $wrestler->pivot->side_number->toEqual(0);
        });
});

test('it adds a tag team to an event match', function () {
    $eventMatch = EventMatch::factory()->create();
    $tagTeam = TagTeam::factory()->create();

    app(EventMatchRepository::class)->addTagTeamToMatch($eventMatch, $tagTeam, 0);

    expect($eventMatch->fresh())
        ->tagTeams->toHaveCount(1)
        ->tagTeams->each(function ($tagTeam) {
            $tagTeam->pivot->side_number->toEqual(0);
        });
});
