<?php

use App\Actions\Managers\CreateAction;
use App\Data\ManagerData;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Requests\Managers\StoreRequest;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

beforeEach(function () {
    $this->data = StoreRequest::factory()->create();
    $this->request = StoreRequest::create(action([ManagersController::class, 'store']), 'POST', $this->data);
});

test('store calls create action and redirects', function () {
    actingAs(administrator())
        ->from(action([ManagersController::class, 'create']))
        ->post(action([ManagersController::class, 'store']), $this->data)
        ->assertValid()
        ->assertRedirect(action([ManagersController::class, 'index']));

    CreateAction::shouldRun()->with(ManagerData::fromStoreRequest($this->request));
});

test('a basic user cannot create a manager', function () {
    actingAs(basicUser())
        ->post(action([ManagersController::class, 'store']), $this->data)
        ->assertForbidden();
});

test('a guest cannot create a manager', function () {
    post(action([ManagersController::class, 'store']), $this->data)
        ->assertRedirect(route('login'));
});
