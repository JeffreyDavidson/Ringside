<?php

use App\Http\Controllers\Events\EventsController;

test('create returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([EventsController::class, 'create']))
        ->assertStatus(200)
        ->assertViewIs('events.create');
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
