<?php

use App\Http\Controllers\Venues\VenuesController;
use App\Http\Requests\Venues\StoreRequest;
use App\Models\Venue;

test('create returns a view', function () {
    $this->actingAs(administrator())
        ->get(action([VenuesController::class, 'create']))
        ->assertViewIs('venues.create')
        ->assertViewHas('venue', new Venue);
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

test('store creates a venue and redirects', function () {
    $data = StoreRequest::factory()->create([
        'name' => 'Example Venue',
        'address1' => '123 Main Street',
        'address2' => 'Suite 100',
        'city' => 'Laraville',
        'state' => 'New York',
        'zip' => '12345',
    ]);

    $this->actingAs(administrator())
        ->from(action([VenuesController::class, 'create']))
        ->post(action([VenuesController::class, 'store']), $data)
        ->assertValid()
        ->assertRedirect(action([VenuesController::class, 'index']));

    expect(Venue::latest()->first()->name)->toBe('Example Venue');
    expect(Venue::latest()->first()->address1)->toBe('123 Main Street');
    expect(Venue::latest()->first()->address2)->toBe('Suite 100');
    expect(Venue::latest()->first()->city)->toBe('Laraville');
    expect(Venue::latest()->first()->state)->toBe('New York');
    expect(Venue::latest()->first()->zip)->toBe('12345');
});

test('a basic user cannot create a venue', function () {
    $data = StoreRequest::factory()->create();

    $this->actingAs(basicUser())
        ->post(action([VenuesController::class, 'store']), $data)
        ->assertForbidden();
});

test('a guest cannot create a venue', function () {
    $data = StoreRequest::factory()->create();

    $this->post(action([VenuesController::class, 'store']), $data)
        ->assertRedirect(route('login'));
});
