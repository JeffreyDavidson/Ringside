<?php

use App\Actions\EventMatches\AddWrestlersToMatchAction;
use App\Models\EventMatch;
use App\Models\Wrestler;
use App\Repositories\EventMatchRepository;
use Database\Seeders\MatchTypesTableSeeder;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->eventMatchRepository = mock(EventMatchRepository::class);
});

test('it adds wrestlers to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $wrestlers = Wrestler::factory()->count(1)->create();
    $sideNumber = 0;

    $this->eventMatchRepository
        ->shouldReceive('addWrestlerToMatch')
        ->with($eventMatch, \Mockery::type(Wrestler::class), $sideNumber)
        ->times($wrestlers->count());

    AddWrestlersToMatchAction::run($eventMatch, $wrestlers, $sideNumber);
});