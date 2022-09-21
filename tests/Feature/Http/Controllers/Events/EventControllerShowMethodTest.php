<?php

use App\Http\Controllers\Events\EventsController;
use App\Models\Event;

beforeEach(function () {
    $this->event = Event::factory()->create();
});

test('show returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([EventsController::class, 'show'], $this->event))
        ->assertViewIs('events.show')
        ->assertViewHas('event', $this->event);
});

test('a basic user cannot view an event profile', function () {
    $this->actingAs(basicUser())
        ->get(action([EventsController::class, 'show'], $this->event))
        ->assertForbidden();
});

test('a guest cannot view an event profile', function () {
    $this->get(action([EventsController::class, 'show'], $this->event))
        ->assertRedirect(route('login'));
});
