<?php

use App\Actions\EventMatches\AddTitlesToMatchAction;
use App\Models\EventMatch;
use App\Models\Title;
use App\Repositories\EventMatchRepository;
use Database\Seeders\MatchTypesTableSeeder;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->eventMatchRepository = mock(EventMatchRepository::class);
});

test('it adds titles to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $titles = Title::factory()->count(1)->create();

    $this->eventMatchRepository
        ->shouldReceive('addTitleToMatch')
        ->with($eventMatch, \Mockery::type(Title::class))
        ->times($titles->count());

    AddTitlesToMatchAction::run($eventMatch, $titles);
});
