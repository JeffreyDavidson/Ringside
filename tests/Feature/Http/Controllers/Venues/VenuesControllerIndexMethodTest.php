<?php

use App\Http\Controllers\Venues\VenuesController;

test('index returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([VenuesController::class, 'index']))
        ->assertOk()
        ->assertViewIs('venues.index')
        ->assertSeeLivewire('venues.venues-list');
});

test('a basic user cannot view venues index page', function () {
    $this->actingAs(basicUser())
        ->get(action([VenuesController::class, 'index']))
        ->assertForbidden();
});

test('a guest cannot view venues index page', function () {
    $this->get(action([VenuesController::class, 'index']))
        ->assertRedirect(route('login'));
});
