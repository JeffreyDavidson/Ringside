<?php

use App\Actions\EventMatches\AddTitlesToMatchAction;
use App\Models\EventMatch;
use App\Models\Title;
use Database\Seeders\MatchTypesTableSeeder;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
});

test('add titles to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $titles = Title::factory()->count(1)->create();

    AddTitlesToMatchAction::run($eventMatch, $titles);

    expect($eventMatch->titles)->collectionHas($titles->first());
});
