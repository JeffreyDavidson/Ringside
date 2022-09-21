<?php

use App\Actions\Stables\RestoreAction;
use App\Http\Controllers\Stables\RestoreController;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;

beforeEach(function () {
    $this->stable = Stable::factory()->trashed()->create();
});

test('invoke calls restore action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    RestoreAction::shouldRun()->with($this->stable);
});

test('a basic user cannot restore a stable', function () {
    $this->actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->stable))
        ->assertForbidden();
});

test('a guest cannot restore a stable', function () {
    $this->patch(action([RestoreController::class], $this->stable))
        ->assertRedirect(route('login'));
});
