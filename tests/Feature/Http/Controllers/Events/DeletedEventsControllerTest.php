<?php

use App\Http\Controllers\Events\DeletedEventsController;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([DeletedEventsController::class, 'index']))
        ->assertOk()
        ->assertViewIs('events.deleted')
        ->assertSeeLivewire('events.deleted-events-list');
});

test('a basic user cannot view deleted events index page', function () {
    $this->actingAs(basicUser())
        ->get(action([DeletedEventsController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view deleted events index page', function () {
    $this->get(action([DeletedEventsController::class, 'index']))
        ->assertRedirect(route('login'));
});
