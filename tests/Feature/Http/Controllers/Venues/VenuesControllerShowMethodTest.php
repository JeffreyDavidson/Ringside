<?php

use App\Http\Controllers\Venues\VenuesController;
use App\Models\Venue;

beforeEach(function () {
    $this->venue = Venue::factory()->create();
});

test('show returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([VenuesController::class, 'show'], $this->venue))
        ->assertOk()
        ->assertViewIs('venues.show')
        ->assertViewHas('venue', $this->venue);
});

test('a basic user cannot view a venue', function () {
    $this->actingAs(basicUser())
        ->get(action([VenuesController::class, 'show'], $this->venue))
        ->assertForbidden();
});

test('a guest cannot view a venue', function () {
    $this->get(action([VenuesController::class, 'show'], $this->venue))
        ->assertRedirect(route('login'));
});
