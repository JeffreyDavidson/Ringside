<?php

use App\Actions\Venues\CreateAction;
use App\Data\VenueData;
use App\Http\Controllers\Venues\VenuesController;
use App\Http\Requests\Venues\StoreRequest;

beforeEach(function () {
    $this->data = StoreRequest::factory()->create();
    $this->request = StoreRequest::create(action([VenuesController::class, 'store']), 'POST', $this->data);
});

test('store calls create action and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([VenuesController::class, 'create']))
        ->post(action([VenuesController::class, 'store']), $this->data)
        ->assertValid()
        ->assertRedirect(action([VenuesController::class, 'index']));

    CreateAction::shouldRun()->with(VenueData::fromStoreRequest($this->request));
});

test('a basic user cannot create a venue', function () {
    $this->actingAs(basicUser())
        ->post(action([VenuesController::class, 'store']), $this->data)
        ->assertForbidden();
});

test('a guest cannot create a venue', function () {
    $this->post(action([VenuesController::class, 'store']), $this->data)
        ->assertRedirect(route('login'));
});
