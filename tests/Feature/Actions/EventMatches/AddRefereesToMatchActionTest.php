<?php

use App\Actions\EventMatches\AddRefereesToMatchAction;
use App\Models\EventMatch;
use App\Models\Referee;
use App\Repositories\EventMatchRepository;
use Database\Seeders\MatchTypesTableSeeder;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->eventMatchRepository = mock(EventMatchRepository::class);
});

test('it adds referees to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $referees = Referee::factory()->count(1)->create();

    $this->eventMatchRepository
        ->shouldReceive('addRefereeToMatch')
        ->with($eventMatch, \Mockery::type(Referee::class))
        ->times($referees->count());

    AddRefereesToMatchAction::run($eventMatch, $referees);
});
