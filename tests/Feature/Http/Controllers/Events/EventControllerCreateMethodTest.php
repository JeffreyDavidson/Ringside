<?php

use App\Http\Controllers\Events\EventsController;
use App\Models\Event;
use App\Models\Venue;

test('create returns a view', function () {
    $venues = Venue::factory()->count(3)->create();

    $this->actingAs(administrator())
        ->get(action([EventsController::class, 'create']))
        ->assertStatus(200)
        ->assertViewIs('events.create')
        ->assertViewHas('event', new Event)
        ->assertViewHas('venues', $venues->pluck('name', 'id')->escapeWhenCastingToString());
});

test('a basic user cannot view the form for creating an event', function () {
    $this->actingAs(basicUser())
        ->get(action([EventsController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating an event', function () {
    $this->get(action([EventsController::class, 'create']))
        ->assertRedirect(route('login'));
});
