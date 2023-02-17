<?php

use App\Actions\Referees\ReinstateAction;
use App\Http\Controllers\Referees\RefereesController;
use App\Http\Controllers\Referees\ReinstateController;
use App\Models\Referee;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\patch;

beforeEach(function () {
    $this->referee = Referee::factory()->suspended()->create();
});

test('invoke calls reinstate action and redirects', function () {
    actingAs(administrator())
        ->patch(action([ReinstateController::class], $this->referee))
        ->assertRedirect(action([RefereesController::class, 'index']));

    ReinstateAction::shouldRun()->with($this->referee);
});

test('a basic user cannot reinstate a referee', function () {
    actingAs(basicUser())
        ->patch(action([ReinstateController::class], $this->referee))
        ->assertForbidden();
});

test('a guest cannot reinstate a referee', function () {
    patch(action([ReinstateController::class], $this->referee))
        ->assertRedirect(route('login'));
});
