<?php

use App\Http\Controllers\Venues\VenuesController;
use App\Http\Requests\Venues\UpdateRequest;
use App\Models\Venue;

test('edit returns a view', function () {
    $venue = Venue::factory()->create();

    $this->actingAs(administrator())
        ->get(action([VenuesController::class, 'edit'], $venue))
        ->assertViewIs('venues.edit')
        ->assertViewHas('venue', $venue);
});

test('a basic user cannot view the form for editing a venue', function () {
    $venue = Venue::factory()->create();

    $this->actingAs(basicUser())
        ->get(action([VenuesController::class, 'edit'], $venue))
        ->assertForbidden();
});

test('a guest cannot view the form for creating a venue', function () {
    $venue = Venue::factory()->create();

    $this->get(action([VenuesController::class, 'edit'], $venue))
        ->assertRedirect(route('login'));
});

test('updates a venue and redirects', function () {
    $venue = Venue::factory()->create([
        'name' => 'Old Venue Name',
        'address1' => '123 Main Street',
        'city' => 'New York City',
        'state' => 'New York',
        'zip' => '12345',
    ]);
    $data = UpdateRequest::factory()->create([
        'name' => 'New Venue Name',
        'address1' => '456 1st Avenue',
        'city' => 'Laraville',
        'state' => 'California',
        'zip' => '67890',
    ]);

    $this->actingAs(administrator())
        ->from(action([VenuesController::class, 'edit'], $venue))
        ->patch(action([VenuesController::class, 'update'], $venue), $data)
        ->assertValid()
        ->assertRedirect(action([VenuesController::class, 'index']));

    expect($venue->fresh())
        ->name->toBe('New Venue Name')
        ->address1->toBe('456 1st Avenue')
        ->city->toBe('Laraville')
        ->state->toBe('California')
        ->zip->toBe('67890');
});

test('a basic user cannot update a venue', function () {
    $venue = Venue::factory()->create();
    $data = UpdateRequest::factory()->create();

    $this->actingAs(basicUser())
        ->from(action([VenuesController::class, 'edit'], $venue))
        ->patch(action([VenuesController::class, 'update'], $venue), $data)
        ->assertForbidden();
});

test('a guest cannot update a venue', function () {
    $venue = Venue::factory()->create();
    $data = UpdateRequest::factory()->create();

    $this->from(action([VenuesController::class, 'edit'], $venue))
        ->patch(action([VenuesController::class, 'update'], $venue), $data)
        ->assertRedirect(route('login'));
});
