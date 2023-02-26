<?php

use App\Http\Controllers\Venues\VenuesController;
use App\Models\Venue;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->venue = Venue::factory()->create();
});

test('edit returns a view', function () {
    actingAs(administrator())
        ->get(action([VenuesController::class, 'edit'], $this->venue))
        ->assertViewIs('venues.edit')
        ->assertViewHas('venue', $this->venue);
});

test('a basic user cannot view the form for editing a venue', function () {
    actingAs(basicUser())
        ->get(action([VenuesController::class, 'edit'], $this->venue))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a venue', function () {
    get(action([VenuesController::class, 'edit'], $this->venue))
        ->assertRedirect(route('login'));
});
