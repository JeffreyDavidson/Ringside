<?php

declare(strict_types=1);

use App\Actions\Matches\AddRefereesToMatchAction;
use App\Models\Matches\EventMatch;
use App\Models\Referees\Referee;
use App\Repositories\MatchRepository;
use Database\Seeders\MatchTypesTableSeeder;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->matchRepository = $this->mock(MatchRepository::class);
    $this->app->instance(MatchRepository::class, $this->matchRepository);
});

test('it adds referees to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $referees = Referee::factory()->bookable()->count(1)->create();

    $this->matchRepository
        ->shouldReceive('addRefereeToMatch')
        ->with($eventMatch, Mockery::type(Referee::class))
        ->times($referees->count());

    AddRefereesToMatchAction::run($eventMatch, $referees);
});
