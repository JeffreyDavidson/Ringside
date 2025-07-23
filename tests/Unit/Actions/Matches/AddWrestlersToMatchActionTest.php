<?php

declare(strict_types=1);

use App\Actions\Matches\AddWrestlersToMatchAction;
use App\Models\Matches\EventMatch;
use App\Models\Wrestlers\Wrestler;
use App\Repositories\MatchRepository;
use Database\Seeders\MatchTypesTableSeeder;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->eventMatchRepository = $this->mock(MatchRepository::class);
});

test('it adds wrestlers to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $wrestlers = Wrestler::factory()->bookable()->count(1)->create();
    $sideNumber = 1;

    $this->eventMatchRepository
        ->shouldReceive('addWrestlerToMatch')
        ->with($eventMatch, Mockery::type(Wrestler::class), $sideNumber)
        ->times($wrestlers->count());

    AddWrestlersToMatchAction::run($eventMatch, $wrestlers, $sideNumber);
});
