<?php

use App\Actions\Wrestlers\SuspendAction;
use App\Http\Controllers\Wrestlers\SuspendController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->bookable()->create();
});

test('invoke calls suspend action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([SuspendController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    SuspendAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot suspend a wrestler', function () {
    $this->actingAs(basicUser())
        ->patch(action([SuspendController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot suspend a wrestler', function () {
    $this->patch(action([SuspendController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});
