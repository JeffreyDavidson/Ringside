<?php

use App\Actions\Referees\SuspendAction;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\SuspendController;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->bookable()->create();
});

test('invoke calls suspend action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([SuspendController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    SuspendAction::shouldRun()->with($this->referee);
});

test('a basic user cannot suspend a referee', function () {
    $this->actingAs(basicUser())
        ->patch(action([SuspendController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot suspend a referee', function () {
    $this->patch(action([SuspendController::class], $this->referee))
        ->assertRedirect(route('login'));
});
