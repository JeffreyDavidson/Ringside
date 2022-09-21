<?php

use App\Http\Controllers\Events\EventsController;
use App\Models\Event;

beforeEach(function () {
    $this->event = Event::factory()->create();
});

test('edit returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([EventsController::class, 'edit'], $this->event))
        ->assertStatus(200)
        ->assertViewIs('events.edit')
        ->assertViewHas('event', $this->event);
});

test('a basic user cannot view the form for editing an event', function () {
    $this->actingAs(basicUser())
        ->get(action([EventsController::class, 'edit'], $this->event))
        ->assertForbidden();
});

test('a guest cannot view the form for editing an event', function () {
    $this->get(action([EventsController::class, 'edit'], $this->event))
        ->assertRedirect(route('login'));
});
