<?php

use App\Http\Controllers\Venues\VenuesController;
use App\Models\Venue;

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

test('show returns a view', function () {
    $venue = Venue::factory()->create();

    $this->actingAs(administrator())
        ->get(action([VenuesController::class, 'show'], $venue))
        ->assertOk()
        ->assertViewIs('venues.show')
        ->assertViewHas('venue', $venue);
});

test('a basic user cannot view a venue', function () {
    $venue = Venue::factory()->create();

    $this->actingAs(basicUser())
        ->get(action([VenuesController::class, 'show'], $venue))
        ->assertForbidden();
});

test('a guest cannot view a venue', function () {
    $venue = Venue::factory()->create();

    $this->get(action([VenuesController::class, 'show'], $venue))
        ->assertRedirect(route('login'));
});

test('deletes a venue and redirects', function () {
    $venue = Venue::factory()->create();

    $this->actingAs(administrator())
        ->delete(action([VenuesController::class, 'destroy'], $venue))
        ->assertRedirect(action([VenuesController::class, 'index']));

    $this->assertSoftDeleted($venue);
});

test('a basic user cannot delete a venue', function () {
    $venue = Venue::factory()->create();

    $this->actingAs(basicUser())
        ->delete(action([VenuesController::class, 'destroy'], $venue))
        ->assertForbidden();
});

test('a guest cannot delete a venue', function () {
    $venue = Venue::factory()->create();

    $this->delete(action([VenuesController::class, 'destroy'], $venue))
        ->assertRedirect(route('login'));
});
