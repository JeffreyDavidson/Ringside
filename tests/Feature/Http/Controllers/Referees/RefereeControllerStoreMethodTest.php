<?php

use App\Actions\Referees\CreateAction;
use App\Data\RefereeData;
use App\Http\Controllers\Managers\ManagersController;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Requests\Referees\StoreRequest;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

beforeEach(function () {
    $this->data = StoreRequest::factory()->create();
    $this->request = StoreRequest::create(action([ManagersController::class, 'store']), 'POST', $this->data);
});

test('store calls create action and redirects', function () {
    actingAs(administrator())
        ->from(action([RefereesController::class, 'create']))
        ->post(action([RefereesController::class, 'store']), $this->data)
        ->assertValid()
        ->assertRedirect(action([RefereesController::class, 'index']));

    CreateAction::shouldRun()->with(RefereeData::fromStoreRequest($this->request));
});

test('a basic user cannot create a referee', function () {
    actingAs(basicUser())
        ->post(action([RefereesController::class, 'store']), $this->data)
        ->assertForbidden();
});

test('a guest cannot create a referee', function () {
    post(action([RefereesController::class, 'store']), $this->data)
        ->assertRedirect(route('login'));
});
