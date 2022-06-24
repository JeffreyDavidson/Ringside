<?php

use App\Http\Controllers\EventMatches\EventMatchesController;
use App\Http\Requests\EventMatches\StoreRequest;
use App\Models\Event;
use Database\Seeders\MatchTypesTableSeeder;
use Facades\App\Actions\EventMatches\AddMatchForEventAction;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->event = Event::factory()->scheduled()->create();
});

test('store creates a match for a scheduled event and redirects', function () {
    $data = StoreRequest::factory()->create();

    $this
        ->actingAs(administrator())
        ->from(action([EventMatchesController::class, 'create'], $this->event))
        ->post(action([EventMatchesController::class, 'store'], $this->event), $data)
        ->assertRedirect(route('events.matches.index', $this->event));

    AddMatchForEventAction::shouldReceive('run');
});

test('a basic user cannot create a match for an event', function () {
    $data = StoreRequest::factory()->create();

    $this->actingAs(basicUser())
        ->post(action([EventMatchesController::class, 'store'], $this->event), $data)
        ->assertForbidden();
});

test('a guest cannot create a match for an event', function () {
    $data = StoreRequest::factory()->create();

    $this->post(action([EventMatchesController::class, 'store'], $this->event), $data)
        ->assertRedirect(route('login'));
});
