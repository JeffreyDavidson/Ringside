<?php

use App\Actions\Wrestlers\ReinstateAction;
use App\Http\Controllers\Wrestlers\ReinstateController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->suspended()->create();
});

test('invoke call reinstate action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([ReinstateController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    ReinstateAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot reinstate a wrestler', function () {
    $this->actingAs(basicUser())
        ->patch(action([ReinstateController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot reinstate a wrestler', function () {
    $this->patch(action([ReinstateController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});
