<?php

use App\Actions\Referees\CreateAction;
use App\Data\RefereeData;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Requests\Referees\StoreRequest;

beforeEach(function () {
    $this->data = StoreRequest::factory()->create();
    $this->request = StoreRequest::create(action([ManagersController::class, 'store']), 'POST', $this->data);
});

test('store calls create action and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([RefereesController::class, 'create']))
        ->post(action([RefereesController::class, 'store']), $this->data)
        ->assertValid()
        ->assertRedirect(action([RefereesController::class, 'index']));

    CreateAction::shouldRun()->with(RefereeData::fromStoreRequest($this->request));
});

test('a basic user cannot create a referee', function () {
    $this->actingAs(basicUser())
        ->post(action([RefereesController::class, 'store']), $this->data)
        ->assertForbidden();
});

test('a guest cannot create a referee', function () {
    $this->post(action([RefereesController::class, 'store']), $this->data)
        ->assertRedirect(route('login'));
});
