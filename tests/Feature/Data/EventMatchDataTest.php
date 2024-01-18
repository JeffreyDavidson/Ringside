<?php

declare(strict_types=1);

use App\Data\EventMatchData;
use App\Http\Controllers\EventMatches\EventMatchesController;
use App\Http\Requests\EventMatches\StoreRequest;
use Database\Seeders\MatchTypesTableSeeder;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->event = \App\Models\Event::factory()->scheduled()->create();
    $this->data = StoreRequest::factory()->singles()->create();
    $this->request = StoreRequest::create(
        action([EventMatchesController::class, 'store'], $this->event),
        'POST',
        $this->data
    );
});

test('it can get competitors for a match', function () {
    $eventMatchData = EventMatchData::fromStoreRequest($this->request);

    dd($eventMatchData->competitors);
});
