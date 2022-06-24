<?php

use App\Actions\EventMatches\AddWrestlersToMatchAction;
use App\Models\EventMatch;
use App\Models\Wrestler;
use Database\Seeders\MatchTypesTableSeeder;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
});

test('add wrestlers to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $wrestlers = Wrestler::factory()->count(1)->create();
    $sideNumber = 0;

    AddWrestlersToMatchAction::run($eventMatch, $wrestlers, $sideNumber);

    expect($eventMatch->wrestlers)->collectionHas($wrestlers->first());
});
