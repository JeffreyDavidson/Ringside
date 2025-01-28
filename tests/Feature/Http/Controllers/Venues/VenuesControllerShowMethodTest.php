<?php

declare(strict_types=1);

use App\Http\Controllers\Venues\VenuesController;
use App\Models\Venue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->venue = Venue::factory()->create();
});

test('show returns a view', function () {
    actingAs(administrator())
        ->get(action([VenuesController::class, 'show'], $this->venue))
        ->assertOk()
        ->assertViewIs('venues.show')
        ->assertViewHas('venue', $this->venue);
});

test('a basic user cannot view a venue', function () {
    actingAs(basicUser())
        ->get(action([VenuesController::class, 'show'], $this->venue))
        ->assertForbidden();
});

test('a guest cannot view a venue', function () {
    get(action([VenuesController::class, 'show'], $this->venue))
        ->assertRedirect(route('login'));
});
