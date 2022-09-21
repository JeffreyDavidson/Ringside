<?php

use App\Actions\Venues\DeleteAction;
use App\Http\Controllers\Venues\VenuesController;
use App\Models\Venue;

beforeEach(function () {
    $this->venue = Venue::factory()->create();
});

test('destroy calls delete action and redirects', function () {
    $this->actingAs(administrator())
        ->delete(action([VenuesController::class, 'destroy'], $this->venue))
        ->assertRedirect(action([VenuesController::class, 'index']));

    DeleteAction::shouldRun()->with($this->venue);
});

test('a basic user cannot delete a venue', function () {
    $this->actingAs(basicUser())
        ->delete(action([VenuesController::class, 'destroy'], $this->venue))
        ->assertForbidden();
});

test('a guest cannot delete a venue', function () {
    $this->delete(action([VenuesController::class, 'destroy'], $this->venue))
        ->assertRedirect(route('login'));
});
