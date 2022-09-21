<?php

use App\Actions\Stables\UpdateAction;
use App\Data\StableData;
use App\Http\Controllers\Stables\StablesController;
use App\Http\Requests\Stables\UpdateRequest;
use App\Models\Stable;

beforeEach(function () {
    $this->stable = Stable::factory()->create();
    $this->data = UpdateRequest::factory()->create();
    $this->request = UpdateRequest::create(action([StablesController::class, 'update'], $this->stable), 'PATCH', $this->data);
});

test('updates calls update action and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([StablesController::class, 'edit'], $this->stable))
        ->patch(action([StablesController::class, 'update'], $this->stable), $this->data)
        ->assertValid()
        ->assertRedirect(action([StablesController::class, 'index']));

    UpdateAction::shouldRun()->with($this->stable, StableData::fromUpdateRequest($this->request));
});

test('a basic user cannot update a stable', function () {
    $this->actingAs(basicUser())
        ->patch(action([StablesController::class, 'update'], $this->stable), $this->data)
        ->assertForbidden();
});

test('a guest cannot update a stable', function () {
    $this->patch(action([StablesController::class, 'update'], $this->stable), $this->data)
        ->assertRedirect(route('login'));
});
