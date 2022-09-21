<?php

use App\Actions\Referees\DeleteAction;
use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->create();
});

test('destroy calls delete action and redirects', function () {
    $this->actingAs(administrator())
        ->delete(action([RefereesController::class, 'destroy'], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    DeleteAction::shouldRun()->with($this->referee);
});

test('a basic user cannot delete a referee', function () {
    $this->actingAs(basicUser())
        ->delete(action([RefereesController::class, 'destroy'], $this->referee))
        ->assertForbidden();
});

test('a guest cannot delete a referee', function () {
    $this->delete(action([RefereesController::class, 'destroy'], $this->referee))
        ->assertRedirect(route('login'));
});
