<?php

use App\Actions\Referees\ReleaseAction;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\ReleaseController;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->bookable()->create();
});

test('invoke calls release action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ReleaseController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    ReleaseAction::shouldRun()->with($this->referee);
});

test('a basic user cannot release a referee', function () {
    $this->actingAs(basicUser())
        ->patch(action([ReleaseController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot release a referee', function () {
    $this->patch(action([ReleaseController::class], $this->referee))
        ->assertRedirect(route('login'));
});
