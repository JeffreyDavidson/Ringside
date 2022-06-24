<?php

use App\Actions\EventMatches\AddCompetitorsToMatchAction;
use App\Actions\EventMatches\AddMatchForEventAction;
use App\Actions\EventMatches\AddRefereesToMatchAction;
use App\Actions\EventMatches\AddTitlesToMatchAction;
use App\Data\EventMatchData;
use App\Http\Requests\EventMatches\StoreRequest;
use App\Models\Event;
use App\Models\Title;
use Database\Seeders\MatchTypesTableSeeder;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->event = Event::factory()->scheduled()->create();
});

test('add a match to an event', function () {
    $requestData = StoreRequest::factory()->create([
        'match_type_id' => 1,
        'preview' => 'This is a general preview.',
    ]);
    $eventMatchData = EventMatchData::fromStoreRequest(new StoreRequest($requestData));

    AddMatchForEventAction::run($this->event, $eventMatchData);

    expect($this->event->matches)->toHaveCount(1);
    expect($this->event->matches->first())
        ->match_type_id->toEqual(1)
        ->preview->toEqual('This is a general preview.');
    AddRefereesToMatchAction::mock()->shouldReceive('run');
    AddCompetitorsToMatchAction::mock()->shouldReceive('run');
    AddTitlesToMatchAction::mock()->shouldNotReceive('run');
});

test('add a title match to an event', function () {
    $requestData = StoreRequest::factory()->create([
        'match_type_id' => 1,
        'titles' => Title::factory()->count(1)->create()->modelKeys(),
        'preview' => 'This is a general preview.',
    ]);
    $eventMatchData = EventMatchData::fromStoreRequest(new StoreRequest($requestData));

    AddMatchForEventAction::run($this->event, $eventMatchData);

    expect($this->event->matches)->toHaveCount(1);
    AddTitlesToMatchAction::mock()->shouldReceive('run');
});
