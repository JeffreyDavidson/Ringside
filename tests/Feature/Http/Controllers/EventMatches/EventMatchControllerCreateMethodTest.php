<?php

use App\Http\Controllers\EventMatches\EventMatchesController;
use App\Models\Event;
use App\Models\EventMatch;
use Database\Seeders\MatchTypesTableSeeder;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->event = Event::factory()->scheduled()->create();
});

test('it ensures the correct view is loaded', function () {
    actingAs(administrator())
        ->get(action([EventMatchesController::class, 'create'], $this->event))
        ->assertOk()
        ->assertViewIs('matches.create');
});

test('it ensures the view is passed the correct variables', function () {
    actingAs(administrator())
        ->get(action([EventMatchesController::class, 'create'], $this->event))
        ->assertOk()
        ->assertViewHas('event', $this->event)
        ->assertViewHas('match', new EventMatch);
});

test('a basic user cannot create a match for an event', function () {
    actingAs(basicUser())
        ->get(action([EventMatchesController::class, 'create'], $this->event))
        ->assertForbidden();
});

test('a guest cannot create a match for an event', function () {
    get(action([EventMatchesController::class, 'create'], $this->event))
        ->assertRedirect(route('login'));
});
