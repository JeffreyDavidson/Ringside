<?php

use App\Actions\Referees\ClearInjuryAction;
use App\Http\Controllers\Referees\ClearInjuryController;
use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->injured()->create();
});

test('invoke calls clear injury action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ClearInjuryController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    ClearInjuryAction::shouldRun()->with($this->referee);
});

test('a basic user cannot mark an injured referee as cleared', function () {
    $this->actingAs(basicUser())
        ->patch(action([ClearInjuryController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot mark an injured referee as cleared', function () {
    $this->patch(action([ClearInjuryController::class], $this->referee))
        ->assertRedirect(route('login'));
});
