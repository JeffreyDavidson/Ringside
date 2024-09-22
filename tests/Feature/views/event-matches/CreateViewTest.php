<?php

declare(strict_types=1);

use App\Livewire\EventMatches\MatchForm;
use App\Models\Event;

use function Pest\Laravel\actingAs;

test('it renders the match form component', function () {
    $event = Event::factory()->scheduled()->create();

    actingAs(administrator());

    $this->view('event-matches.create', ['event' => $event])
        ->assertSeeLivewire(MatchForm::class);
});
