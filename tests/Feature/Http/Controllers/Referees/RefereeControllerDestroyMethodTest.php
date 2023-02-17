<?php

use App\Actions\Referees\DeleteAction;
use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;

beforeEach(function () {
    $this->referee = Referee::factory()->create();
});

test('destroy calls delete action and redirects', function () {
    actingAs(administrator())
        ->delete(action([RefereesController::class, 'destroy'], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    DeleteAction::shouldRun()->with($this->referee);
});

test('a basic user cannot delete a referee', function () {
    actingAs(basicUser())
        ->delete(action([RefereesController::class, 'destroy'], $this->referee))
        ->assertForbidden();
});

test('a guest cannot delete a referee', function () {
    delete(action([RefereesController::class, 'destroy'], $this->referee))
        ->assertRedirect(route('login'));
});
