<?php

use App\Actions\Venues\DeleteAction;
use App\Http\Controllers\Venues\VenuesController;
use App\Models\Venue;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;

beforeEach(function () {
    $this->venue = Venue::factory()->create();
});

test('destroy calls delete action and redirects', function () {
    actingAs(administrator())
        ->delete(action([VenuesController::class, 'destroy'], $this->venue))
        ->assertRedirect(action([VenuesController::class, 'index']));

    DeleteAction::shouldRun()->with($this->venue);
});

test('a basic user cannot delete a venue', function () {
    actingAs(basicUser())
        ->delete(action([VenuesController::class, 'destroy'], $this->venue))
        ->assertForbidden();
});

test('a guest cannot delete a venue', function () {
    delete(action([VenuesController::class, 'destroy'], $this->venue))
        ->assertRedirect(route('login'));
});
