<?php

use App\Actions\Referees\InjureAction;
use App\Http\Controllers\Referees\InjureController;
use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->bookable()->create();
});

test('invoke calls injure action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([InjureController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    InjureAction::shouldRun()->with($this->referee);
});

test('a basic user cannot injure a referee', function () {
    $this->actingAs(basicUser())
        ->patch(action([InjureController::class], $this->referee))
        ->assertForbidden();
});

test('a guest user cannot injure a referee', function () {
    $this->patch(action([InjureController::class], $this->referee))
        ->assertRedirect(route('login'));
});
