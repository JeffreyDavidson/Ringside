<?php

declare(strict_types=1);

use App\Actions\Matches\AddRefereesToMatchAction;
use App\Models\Matches\EventMatch;
use App\Models\Referees\Referee;
use App\Repositories\EventMatchRepository;
use Database\Seeders\MatchTypesTableSeeder;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->eventMatchRepository = $this->mock(EventMatchRepository::class);
});

test('it adds referees to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $referees = Referee::factory()->available()->count(1)->create();

    $this->eventMatchRepository
        ->shouldReceive('addRefereeToMatch')
        ->with($eventMatch, Mockery::type(Referee::class))
        ->times($referees->count());

    AddRefereesToMatchAction::run($eventMatch, $referees);
});
