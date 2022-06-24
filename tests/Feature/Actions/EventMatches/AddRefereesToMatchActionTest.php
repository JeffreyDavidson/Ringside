<?php

use App\Actions\EventMatches\AddRefereesToMatchAction;
use App\Models\EventMatch;
use App\Models\Referee;
use Database\Seeders\MatchTypesTableSeeder;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
});

test('add referees to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $referees = Referee::factory()->count(1)->create();

    AddRefereesToMatchAction::run($eventMatch, $referees);

    expect($eventMatch->referees)->collectionHas($referees->first());
});
