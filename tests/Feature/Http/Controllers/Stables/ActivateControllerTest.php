<?php

use App\Actions\Stables\ActivateAction;
use App\Http\Controllers\Stables\ActivateController;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;

beforeEach(function () {
    $this->stable = Stable::factory()->unactivated()->create();
});

test('invoke calls activate action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ActivateController::class], $this->stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    ActivateAction::shouldRun()->with($this->stable);
});

test('a basic user cannot activate a stable', function () {
    $this->actingAs(basicUser())
        ->patch(action([ActivateController::class], $this->stable))
        ->assertForbidden();
});

test('a guest cannot activate a stable', function () {
    $this->patch(action([ActivateController::class], $this->stable))
        ->assertRedirect(route('login'));
});
