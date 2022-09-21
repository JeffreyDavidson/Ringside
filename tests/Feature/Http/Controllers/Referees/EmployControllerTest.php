<?php

use App\Actions\Referees\EmployAction;
use App\Http\Controllers\Referees\EmployController;
use App\Http\Controllers\Referees\RefereesController;
use App\Models\Referee;

beforeEach(function () {
    $this->referee = Referee::factory()->unemployed()->create();
});

test('invoke calls employ action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([EmployController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    EmployAction::shouldRun()->with($this->referee);
});

test('a basic user cannot employ a referee', function () {
    $this->actingAs(basicUser())
        ->patch(action([EmployController::class], $this->referee))
        ->assertForbidden();
});

test('a guest user cannot employ a referee', function () {
    $this->patch(action([EmployController::class], $this->referee))
        ->assertRedirect(route('login'));
});
