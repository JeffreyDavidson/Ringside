<?php

use App\Actions\Stables\DeleteAction;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;

beforeEach(function () {
    $this->stable = Stable::factory()->create();
});

test('destroy calls delete action and redirects', function () {
    $this->actingAs(administrator())
        ->delete(action([StablesController::class, 'destroy'], $this->stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    DeleteAction::shouldRun()->with($this->stable);
});

test('a basic user cannot delete a stable', function () {
    $this->actingAs(basicUser())
        ->delete(action([StablesController::class, 'destroy'], $this->stable))
        ->assertForbidden();
});

test('a guest cannot delete a stable', function () {
    $this->delete(action([StablesController::class, 'destroy'], $this->stable))
        ->assertRedirect(route('login'));
});
