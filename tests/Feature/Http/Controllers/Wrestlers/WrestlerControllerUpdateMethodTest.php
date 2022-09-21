<?php

use App\Actions\Wrestlers\UpdateAction;
use App\Data\WrestlerData;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Http\Requests\Wrestlers\UpdateRequest;
use App\Models\Wrestler;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->create();
    $this->data = UpdateRequest::factory()->create();
    $this->request = UpdateRequest::create(action([WrestlersController::class, 'update'], $this->wrestler), 'PATCH', $this->data);
});

test('update calls update action and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([WrestlersController::class, 'edit'], $this->wrestler))
        ->patch(action([WrestlersController::class, 'update'], $this->wrestler), $this->data)
        ->assertValid()
        ->assertRedirect(action([WrestlersController::class, 'index']));

    UpdateAction::shouldRun()->with($this->wrestler, WrestlerData::fromUpdateRequest($this->request));
});

test('a basic user cannot update a wrestler', function () {
    $this->actingAs(basicUser())
        ->patch(action([WrestlersController::class, 'update'], $this->wrestler), $this->data)
        ->assertForbidden();
});

test('a guest cannot update a wrestler', function () {
    $this->patch(action([WrestlersController::class, 'update'], $this->wrestler), $this->data)
        ->assertRedirect(route('login'));
});
