<?php

use App\Actions\Wrestlers\UnretireAction;
use App\Http\Controllers\Wrestlers\UnretireController;
use App\Http\Controllers\Wrestlers\WrestlersController;
use App\Models\Wrestler;

beforeEach(function () {
    $this->wrestler = Wrestler::factory()->retired()->create();
});

test('invoke calls unretire action and redirects', function () {
    $this->actingAs(administrator())
        ->patch(action([UnretireController::class], $this->wrestler))
        ->assertRedirect(action([WrestlersController::class, 'index']));

    UnretireAction::shouldRun()->with($this->wrestler);
});

test('a basic user cannot unretire a wrestler', function () {
    $this->actingAs(basicUser())
        ->patch(action([UnretireController::class], $this->wrestler))
        ->assertForbidden();
});

test('a guest cannot unretire a wrestler', function () {
    $this->patch(action([UnretireController::class], $this->wrestler))
        ->assertRedirect(route('login'));
});
