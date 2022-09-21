<?php

use App\Actions\Wrestlers\CreateAction;
use App\Data\WrestlerData;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Http\Requests\Wrestlers\StoreRequest;

beforeEach(function () {
    $this->data = StoreRequest::factory()->create();
    $this->request = StoreRequest::create(action([WrestlersController::class, 'store']), 'POST', $this->data);
});

test('store calls create action and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([WrestlersController::class, 'create']))
        ->post(action([WrestlersController::class, 'store']), $this->data)
        ->assertValid()
        ->assertRedirect(action([WrestlersController::class, 'index']));

    CreateAction::shouldRun()->with(WrestlerData::fromStoreRequest($this->request));
});

test('a basic user cannot create a wrestler', function () {
    $this->actingAs(basicUser())
        ->post(action([WrestlersController::class, 'store']), $this->data)
        ->assertForbidden();
});

test('a guest cannot create a wrestler', function () {
    $this->post(action([WrestlersController::class, 'store']), $this->data)
        ->assertRedirect(route('login'));
});
