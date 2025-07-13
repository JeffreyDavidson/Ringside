<?php

declare(strict_types=1);

use App\Actions\Matches\AddTitlesToMatchAction;
use App\Models\Matches\EventMatch;
use App\Models\Titles\Title;
use App\Repositories\EventMatchRepository;
use Database\Seeders\MatchTypesTableSeeder;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->eventMatchRepository = $this->mock(EventMatchRepository::class);
});

test('it adds titles to a match', function () {
    $eventMatch = EventMatch::factory()->create();
    $titles = Title::factory()->active()->count(1)->create();

    $this->eventMatchRepository
        ->shouldReceive('addTitleToMatch')
        ->with($eventMatch, Mockery::type(Title::class))
        ->times($titles->count());

    AddTitlesToMatchAction::run($eventMatch, $titles);
});
