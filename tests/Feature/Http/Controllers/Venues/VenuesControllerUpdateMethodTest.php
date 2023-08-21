<?php

use App\Actions\Venues\UpdateAction;
use App\Data\VenueData;
use App\Http\Controllers\Venues\VenuesController;
use App\Http\Requests\Venues\UpdateRequest;
use App\Models\Venue;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\from;

beforeEach(function () {
    $this->venue = Venue::factory()->create();
    $this->data = UpdateRequest::factory()->create();
    $this->request = UpdateRequest::create(action([VenuesController::class, 'update'], $this->venue), 'PATCH', $this->data);
});

test('updates calls update action and redirects', function () {
    actingAs(administrator())
        ->from(action([VenuesController::class, 'edit'], $this->venue))
        ->patch(action([VenuesController::class, 'update'], $this->venue), $this->data)
        ->assertValid()
        ->assertRedirect(action([VenuesController::class, 'index']));

    UpdateAction::shouldRun()->with($this->venue, VenueData::fromUpdateRequest($this->request));
});

test('a basic user cannot update a venue', function () {
    actingAs(basicUser())
        ->from(action([VenuesController::class, 'edit'], $this->venue))
        ->patch(action([VenuesController::class, 'update'], $this->venue), $this->data)
        ->assertForbidden();
});

test('a guest cannot update a venue', function () {
    from(action([VenuesController::class, 'edit'], $this->venue))
        ->patch(action([VenuesController::class, 'update'], $this->venue), $this->data)
        ->assertRedirect(route('login'));
});
