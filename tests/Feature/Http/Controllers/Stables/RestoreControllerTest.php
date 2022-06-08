<?php

use App\Http\Controllers\Stables\RestoreController;
use App\Http\Controllers\Stables\StablesController;
use App\Models\Stable;

beforeEach(function () {
    $this->stable = Stable::factory()->trashed()->create();
});

test('invoke restores a deleted stable and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->stable))
        ->assertRedirect(action([StablesController::class, 'index']));

    $this->assertNull($this->stable->fresh()->deleted_at);
});

test('a basic user cannot restore a deleted stable', function () {
    $this->actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->stable))
        ->assertForbidden();
});

test('a guest cannot restore a deleted stable', function () {
    $this->patch(action([RestoreController::class], $this->stable))
        ->assertRedirect(route('login'));
});
