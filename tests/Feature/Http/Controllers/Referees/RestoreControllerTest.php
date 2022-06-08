<?php

use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\RestoreController;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->trashed()->create();
});

test('invoke restores a deleted referee and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    $this->assertNull($this->referee->fresh()->deleted_at);
});

test('a basic user cannot restore a deleted referee', function () {
    $this->actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot restore a deleted referee', function () {
    $this->patch(action([RestoreController::class], $this->referee))
        ->assertRedirect(route('login'));
});
