<?php

use App\Http\Controllers\Wrestlers\RestoreController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->trashed()->create();
});

test('invoke restores a deleted wrestler and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    $this->assertNull($this->wrestler->fresh()->deleted_at);
});

test('a basic user cannot restore a deleted wrestler', function () {
    $this->actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot restore a deleted wrestler', function () {
    $this->patch(action([RestoreController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});
