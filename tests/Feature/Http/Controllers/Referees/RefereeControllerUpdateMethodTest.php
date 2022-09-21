<?php

use App\Actions\Referees\UpdateAction;
use App\Data\RefereeData;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Requests\Referees\UpdateRequest;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->create();
    $this->data = UpdateRequest::factory()->create();
    $this->request = UpdateRequest::create(action([RefereesController::class, 'update'], $this->referee), 'PATCH', $this->data);
});

test('update calls update action and redirects', function () {
    $this->actingAs(administrator())
        ->from(action([RefereesController::class, 'edit'], $this->referee))
        ->patch(action([RefereesController::class, 'update'], $this->referee), $this->data)
        ->assertValid()
        ->assertRedirect(action([RefereesController::class, 'index']));

    UpdateAction::shouldRun()->with($this->referee, RefereeData::fromUpdateRequest($this->request));
});

test('a basic user cannot update a referee', function () {
    $this->actingAs(basicUser())
        ->patch(action([RefereesController::class, 'update'], $this->referee), $this->data)
        ->assertForbidden();
});

test('a guest cannot update a referee', function () {
    $this->patch(action([RefereesController::class, 'update'], $this->referee), $this->data)
        ->assertRedirect(route('login'));
});
