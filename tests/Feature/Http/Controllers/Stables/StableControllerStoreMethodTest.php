<?php

use App\Actions\Stables\CreateAction;
use App\Data\StableData;
use App\Http\Controllers\Stables\StablesController;
use App\Http\Requests\Stables\StoreRequest;

beforeEach(function () {
    $this->data = StoreRequest::factory()->create();
    $this->request = StoreRequest::create(action([StablesController::class, 'store']), 'POST', $this->data);
});

test('store calls create action and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([StablesController::class, 'create']))
        ->post(action([StablesController::class, 'store']), $this->data)
        ->assertValid()
        ->assertRedirect(action([StablesController::class, 'index']));

    CreateAction::shouldRun()->with(StableData::fromStoreRequest($this->request));
});

test('a basic user cannot create a stable', function () {
    $this->actingAs(basicUser())
        ->post(action([StablesController::class, 'store']), $this->data)
        ->assertForbidden();
});

test('a guest cannot create a stable', function () {
    $this->post(action([StablesController::class, 'store']), $this->data)
        ->assertRedirect(route('login'));
});
