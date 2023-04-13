<?php

use App\Models\Event;
use App\Models\EventMatch;
use Database\Seeders\MatchTypesTableSeeder;
use Illuminate\Foundation\Testing\Concerns\InteractsWithViews;

uses(InteractsWithViews::class);

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
});

test('it contains the match form component', function () {
    $event = Event::factory()->create();
    $match = EventMatch::factory()->make();

    $this->actingAs(administrator())
        ->view('matches.create', ['event' => $event])
        ->assertSeeLivewire('event-matches.match-form');
});
