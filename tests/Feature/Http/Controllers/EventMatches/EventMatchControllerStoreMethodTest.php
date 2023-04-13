<?php

use App\Actions\EventMatches\AddMatchForEventAction;
use App\Http\Controllers\EventMatches\EventMatchesController;
use App\Http\Requests\EventMatches\StoreRequest;
use App\Models\Event;
use Database\Seeders\MatchTypesTableSeeder;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

beforeEach(function () {
    $this->seed(MatchTypesTableSeeder::class);
    $this->event = Event::factory()->scheduled()->create();
    $this->data = StoreRequest::factory()->create();
    $this->request = StoreRequest::create(action([EventMatchesController::class, 'store'], $this->event), 'POST', $this->data);
});

test('it saves a match for an event and redirects', function () {
    actingAs(administrator())
        ->from(action([EventMatchesController::class, 'create'], $this->event))
        ->post(action([EventMatchesController::class, 'store'], $this->event), $this->data)
        ->assertRedirect(action([EventMatchesController::class, 'index'], $this->event));

    AddMatchForEventAction::shouldRun($this->event, $this->data);
});

test('a basic user cannot create a match for an event', function () {
    actingAs(basicUser())
        ->post(action([EventMatchesController::class, 'store'], $this->event), $this->data)
        ->assertForbidden();
});

test('a guest cannot create a match for an event', function () {
    post(action([EventMatchesController::class, 'store'], $this->event), $this->data)
        ->assertRedirect(route('login'));
});
