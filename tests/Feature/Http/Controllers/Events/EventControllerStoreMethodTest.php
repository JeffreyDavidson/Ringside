<?php

use App\Actions\Events\CreateAction;
use App\Data\EventData;
use App\Http\Controllers\Events\EventsController;
use App\Http\Requests\Events\StoreRequest;

beforeEach(function () {
    $this->data = StoreRequest::factory()->create();
    $this->request = StoreRequest::create(action([EventsController::class, 'store']), 'POST', $this->data);
});

test('store calls create action and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([EventsController::class, 'create']))
        ->post(action([EventsController::class, 'store']), $this->data)
        ->assertValid()
        ->assertRedirect(action([EventsController::class, 'index']));

    CreateAction::shouldRun()->with(EventData::fromStoreRequest($this->request));
});

test('a basic user cannot create an event', function () {
    $this->actingAs(basicUser())
        ->post(action([EventsController::class, 'store']), $this->data)
        ->assertForbidden();
});

test('a guest cannot create an event', function () {
    $this->post(action([EventsController::class, 'store']), $this->data)
        ->assertRedirect(route('login'));
});
