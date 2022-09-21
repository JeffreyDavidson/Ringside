<?php

use App\Actions\Referees\RestoreAction;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\RestoreController;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->trashed()->create();
});

test('invoke calls restore action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([RestoreController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    RestoreAction::shouldRun()->with($this->referee);
});

test('a basic user cannot restore a referee', function () {
    $this->actingAs(basicUser())
        ->patch(action([RestoreController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot restore a referee', function () {
    $this->patch(action([RestoreController::class], $this->referee))
        ->assertRedirect(route('login'));
});
