<?php

use App\Actions\Referees\UnretireAction;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\UnretireController;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->retired()->create();
});

test('invoke calls unretire action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    UnretireAction::shouldRun()->with($this->referee);
});

test('a basic user cannot unretire a referee', function () {
    $this->actingAs(basicUser())
        ->patch(action([UnretireController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot unretire a referee', function () {
    $this->patch(action([UnretireController::class], $this->referee))
        ->assertRedirect(route('login'));
});
