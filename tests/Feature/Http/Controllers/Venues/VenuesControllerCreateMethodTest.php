<?php

use App\Http\Controllers\Venues\VenuesController;

test('create returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([VenuesController::class, 'create']))
        ->assertViewIs('venues.create');
});

test('a basic user cannot view the form for creating a venue', function () {
    $this->actingAs(basicUser())
        ->get(action([VenuesController::class, 'create']))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a venue', function () {
    $this->get(action([VenuesController::class, 'create']))
        ->assertRedirect(route('login'));
});
