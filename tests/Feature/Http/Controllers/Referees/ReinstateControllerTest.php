<?php

use App\Actions\Referees\ReinstateAction;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\ReinstateController;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->suspended()->create();
});

test('invoke calls reinstate action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    ReinstateAction::shouldRun()->with($this->referee);
});

test('a basic user cannot reinstate a referee', function () {
    $this->actingAs(basicUser())
        ->patch(action([ReinstateController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot reinstate a referee', function () {
    $this->patch(action([ReinstateController::class], $this->referee))
        ->assertRedirect(route('login'));
});
