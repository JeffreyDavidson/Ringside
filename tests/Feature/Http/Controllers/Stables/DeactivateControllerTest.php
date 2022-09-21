<?php

use App\Actions\Stables\DeactivateAction;
use App\Http\Controllers\Stables\DeactivateController;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;

beforeEach(function () {
    $this->stable = Stable::factory()->active()->create();
});

test('invoke calls deactivate action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([DeactivateController::class], $this->stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    DeactivateAction::shouldRun()->with($this->stable);
});

test('a basic user cannot deactivate a stable', function () {
    $this->actingAs(basicUser())
        ->patch(action([DeactivateController::class], $this->stable))
        ->assertForbidden();
});

test('a guest cannot activate a stable', function () {
    $this->patch(action([DeactivateController::class], $this->stable))
        ->assertRedirect(route('login'));
});
