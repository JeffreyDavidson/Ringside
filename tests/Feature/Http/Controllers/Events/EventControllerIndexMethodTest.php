<?php

use App\Http\Controllers\Events\EventsController;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([EventsController::class, 'index']))
        ->assertOk()
        ->assertViewIs('events.index')
        ->assertSeeLivewire('events.events-list');
});

test('a basic user cannot view events index page', function () {
    $this->actingAs(basicUser())
        ->get(action([EventsController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view events index page', function () {
    $this->get(action([EventsController::class, 'index']))
        ->assertRedirect(route('login'));
});
