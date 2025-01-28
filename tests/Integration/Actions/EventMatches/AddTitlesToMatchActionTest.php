<?php

declare(strict_types=1);

use App\Actions\EventMatches\AddTitlesToMatchAction;
use App\Models\EventMatch;
use App\Models\Title;
use App\Repositories\EventMatchRepository;
use Database\Seeders\MatchTypesTableSeeder;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->eventMatchRepository = $this->mock(EventMatchRepository::class);
});

test('it adds titles to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $titles = Title::factory()->count(1)->create();

    $this->eventMatchRepository
        ->shouldReceive('addTitleToMatch')
        ->with($eventMatch, \Mockery::type(Title::class))
        ->times($titles->count());

    app(AddTitlesToMatchAction::class)->handle($eventMatch, $titles);
});
