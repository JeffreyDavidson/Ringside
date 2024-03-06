<?php

declare(strict_types=1);

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

test('a guest cannot create a match for an event', function () {
    get(action([EventMatchesController::class, 'create'], $this->event))
        ->assertRedirect(route('login'));
});

test('a basic user cannot create a match for an event', function () {
    actingAs(basicUser())
        ->get(action([EventMatchesController::class, 'create'], $this->event))
        ->assertForbidden();
});

test('it loads the correct view', function () {
    actingAs(administrator())
        ->get(action([EventMatchesController::class, 'create'], $this->event))
        ->assertOk()
        ->assertViewIs('event-matches.create');
});

test('it passes the event to the view', function () {
    actingAs(administrator())
        ->get(action([EventMatchesController::class, 'create'], $this->event))
        ->assertOk()
        ->assertViewHas('event', $this->event);
});

test('it passes a new event match to the view', function () {
    actingAs(administrator())
        ->get(action([EventMatchesController::class, 'create'], $this->event))
        ->assertOk()
        ->assertViewHas('match', new EventMatch);
});
